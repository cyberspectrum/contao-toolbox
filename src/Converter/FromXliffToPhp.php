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
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * This class converts Xliff files to PHP files.
 */
class FromXliffToPhp extends AbstractConverter
{
    /**
     * The project slug.
     *
     * @var string
     */
    private $projectSlug;

    /**
     * Create a new instance.
     *
     * @param string          $projectSlug  The project slug.
     * @param string          $contaoPath   The root directory for the Contao languages.
     * @param string          $xliffPath    The root directory for the Xliff languages.
     * @param string          $baseLanguage The base language.
     * @param LoggerInterface $logger       The logger to use.
     */
    public function __construct($projectSlug, $contaoPath, $xliffPath, $baseLanguage, LoggerInterface $logger)
    {
        parent::__construct($contaoPath, $xliffPath, $baseLanguage, $logger);
        $this->projectSlug = (string) $projectSlug;
    }

    /**
     * {@inheritDoc}
     */
    protected function collectResourceNamesFromBaseLanguage()
    {
        $finder = new Finder();
        $finder
            ->in($this->xliffPath . DIRECTORY_SEPARATOR . $this->baseLanguage)
            ->name('*.xlf');
        foreach ($this->ignoredResources as $ignoredFile) {
            $finder->notName($ignoredFile . '.xlf');
        }

        $files = [];
        foreach ($finder as $file) {
            $files[] = basename($file->getFilename(), '.xlf');
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
            $this->logger->info('processing resource: {file}...', ['file' => $resource]);

            if (null === ($src = $this->createSourceXliff($resource, $language))) {
                continue;
            }
            $destination = $this->createDestinationContaoFile($src->getOriginal(), $language);

            if (TranslationSync::syncFrom($src, $destination, true, $this->logger)) {
                $destination->setLanguage($language);
                $destination->setTransifexProject($this->projectSlug);
                $destination->setLastChange($src->getDate());
                $destination->save();
            }
        }

        $this->cleanupObsoleteFiles($this->contaoPath . DIRECTORY_SEPARATOR . $language, $resources, '.php');
    }

    /**
     * Retrieve the source Xliff file.
     *
     * @param string $resource The resource name.
     * @param string $language The language code.
     *
     * @return XliffFile|null
     *
     * @throws InvalidArgumentException When the domain does not match the original value in the Xliff.
     */
    private function createSourceXliff($resource, $language)
    {
        $srcFile = $this->xliffPath . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $resource . '.xlf';
        // not a file from transifex received yet.
        if (!file_exists($srcFile)) {
            return null;
        }

        $xliff = new XliffFile($srcFile, $this->logger);

        if ($xliff->getOriginal() !== $resource) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unexpected domain "%s" found in file "%s" instead of domain "%s"',
                    $xliff->getOriginal(),
                    $srcFile,
                    $resource
                )
            );
        }

        return $xliff->setMode('target');
    }

    /**
     * Create a destination file.
     *
     * @param string $resource The resource name.
     * @param string $language The language code.
     *
     * @return ContaoFile
     */
    private function createDestinationContaoFile($resource, $language)
    {
        $dstFile = $this->contaoPath . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $resource . '.php';

        return new ContaoFile($dstFile, $this->logger);
    }
}
