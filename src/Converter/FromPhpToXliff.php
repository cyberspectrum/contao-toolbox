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
    /**
     * {@inheritDoc}
     */
    protected function collectResourceNamesFromBaseLanguage()
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
            $files[] = basename($file->getFilename(), '.php');
        }

        return $files;
    }

    /**
     * {@inheritDoc}
     */
    protected function collectLanguages()
    {
        $locator = new LanguageDirectoryLocator($this->xliffPath, $this->logger);
        return $locator->determineLanguages($this->onlyLanguages);
    }

    /**
     * {@inheritDoc}
     */
    protected function processLanguage($resources, $language)
    {
        $this->logger->info('processing language: {language}...', ['language' => $language]);
        foreach ($resources as $resource) {
            $this->logger->info('processing file: {file}...', ['file' => $resource]);

            $source      = $this->createSourcePhp($resource, $language);
            $base        = $this->createBasePhp($resource);
            $destination = $this->createDestinationXliffFile($resource, $language);
            if (file_exists($source->getFileName())) {
                $time = filemtime($source->getFileName());
            } else {
                $time = filemtime($base->getFileName());
            }
            $destination->setDate($time);

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
     * @return ContaoFile|null
     *
     * @throws InvalidArgumentException When the domain does not match the original value in the Xliff.
     */
    private function createSourcePhp($resource, $language)
    {
        $srcFile = $this->contaoPath . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $resource . '.php';
        return new ContaoFile($srcFile, $this->logger);
    }

    /**
     * Retrieve the source Contao file.
     *
     * @param string $resource The resource name.
     *
     * @return ContaoFile|null
     *
     * @throws InvalidArgumentException When the domain does not match the original value in the Xliff.
     */
    private function createBasePhp($resource)
    {
        return $this->createSourcePhp($resource, $this->baseLanguage);
    }

    /**
     * Create a destination file.
     *
     * @param string $resource The resource name.
     * @param string $language The language code.
     *
     * @return XliffFile
     */
    private function createDestinationXliffFile($resource, $language)
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
