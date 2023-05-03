<?php

/**
 * This file is part of cyberspectrum/contao-toolbox.
 *
 * (c) 2013-2017 CyberSpectrum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    cyberspectrum/contao-toolbox.
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\ContaoToolBox\Converter;

use CyberSpectrum\ContaoToolBox\Locator\LanguageDirectoryLocator;
use CyberSpectrum\ContaoToolBox\Translation\Contao\ContaoFile;
use CyberSpectrum\ContaoToolBox\Translation\TranslationSync;
use CyberSpectrum\ContaoToolBox\Translation\Xliff\XliffFile;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;

/**
 * This class converts PHP files to Xliff files.
 */
class FromPhpToXliff extends AbstractConverter
{
    protected function collectResourceNamesFromBaseLanguage(): array
    {
        $finder = new Finder();
        $finder
            ->in($this->contaoPath . DIRECTORY_SEPARATOR . $this->baseLanguage)
            ->name('*.php');
        foreach ($this->ignoredResources as $ignoredFile) {
            $finder->notName($ignoredFile . '.php');
        }

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getBasename('.' . $file->getExtension());
        }

        return $files;
    }

    protected function collectLanguages(): array
    {
        $locator = new LanguageDirectoryLocator($this->xliffPath, $this->logger);
        return $locator->determineLanguages($this->onlyLanguages);
    }

    protected function processLanguage(array $resources, string $language): void
    {
        $this->logger->info('processing language: {language}...', ['language' => $language]);
        foreach ($resources as $resource) {
            $this->logger->info('processing file: {file}...', ['file' => $resource]);

            $source      = $this->createSourcePhp($resource, $language);
            $base        = $this->createBasePhp($resource);
            $destination = $this->createDestinationXliffFile($resource, $language);
            $time = $source->getLastChange() ?? $base->getLastChange();
            if (null !== $time) {
                $destination->setDate($time);
            }

            // Synchronize all target values from source file to XLIFF file.
            TranslationSync::syncFrom($source, $destination->setMode('target'), false, $this->logger);
            // Synchronize all source values from base file to XLIFF file and remove obsolete keys in destination that
            // are not present in base file anymore.
            TranslationSync::syncFrom($base, $destination->setMode('source'), true, $this->logger);
            $destination->save();
        }

        $this->cleanupObsoleteFiles($this->xliffPath . DIRECTORY_SEPARATOR . $language, $resources, '.xlf');
    }

    /**
     * Retrieve the source Contao file.
     *
     * @param string $resource The resource name.
     * @param string $language The language code.
     *
     * @throws InvalidArgumentException When the domain does not match the original value in the Xliff.
     */
    private function createSourcePhp(string $resource, string $language): ContaoFile
    {
        $srcFile = $this->contaoPath . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $resource . '.php';
        return new ContaoFile($srcFile, $this->logger);
    }

    /**
     * Retrieve the source Contao file.
     *
     * @param string $resource The resource name.
     *
     * @throws InvalidArgumentException When the domain does not match the original value in the Xliff.
     */
    private function createBasePhp(string $resource): ContaoFile
    {
        return $this->createSourcePhp($resource, $this->baseLanguage);
    }

    /**
     * Create a destination file.
     *
     * @param string $resource The resource name.
     * @param string $language The language code.
     */
    private function createDestinationXliffFile(string $resource, string $language): XliffFile
    {
        $dstFile     = $this->xliffPath . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $resource . '.xlf';
        $destination = new XliffFile($dstFile, $this->logger);
        $destination->setDataType('php');
        $destination->setSrcLang($this->baseLanguage);
        $destination->setTgtLang($language);
        $destination->setOriginal($resource);

        return $destination;
    }
}
