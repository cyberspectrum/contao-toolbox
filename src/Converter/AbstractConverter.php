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

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * This class is the abstract base for the converters.
 */
abstract class AbstractConverter
{
    /**
     * The logger to use.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * The Contao language directory.
     *
     * @var string
     */
    protected $contaoPath;

    /**
     * The Xliff language directory.
     *
     * @var string
     */
    protected $xliffPath;

    /**
     * The defined base language.
     *
     * @var string
     */
    protected $baseLanguage;

    /**
     * Optional whitelist for languages.
     *
     * @var string[]
     */
    protected $onlyLanguages = [];

    /**
     * List of ignored files.
     *
     * @var string[]
     */
    protected $ignoredResources = [];

    /**
     * Flag if obsolete resources shall be removed.
     *
     * @var bool
     */
    private $cleanupObsolete = false;

    /**
     * Create a new instance.
     *
     * @param string          $contaoPath   The root directory for the Contao languages.
     * @param string          $xliffPath    The root directory for the Xliff languages.
     * @param string          $baseLanguage The base language.
     * @param LoggerInterface $logger       The logger to use.
     */
    public function __construct($contaoPath, $xliffPath, $baseLanguage, LoggerInterface $logger)
    {
        $this->contaoPath   = (string) $contaoPath;
        $this->xliffPath    = (string) $xliffPath;
        $this->baseLanguage = (string) $baseLanguage;
        $this->logger       = $logger;
    }

    /**
     * Set language whitelist to only convert certain languages.
     *
     * @param string[] $onlyLanguages The new value, pass empty array to clear whitelist.
     *
     * @return AbstractConverter
     */
    public function setOnlyLanguages($onlyLanguages = [])
    {
        $this->onlyLanguages = $onlyLanguages;

        return $this;
    }

    /**
     * Set cleanup obsolete flag.
     *
     * @param bool $cleanupObsolete The value.
     *
     * @return AbstractConverter
     */
    public function setCleanupObsolete($cleanupObsolete = true)
    {
        $this->cleanupObsolete = (bool) $cleanupObsolete;

        return $this;
    }

    /**
     * Set resource blacklist to skip converting certain resources.
     *
     * @param string[] $ignoredResources The new value, pass empty array to clear blacklist.
     *
     * @return AbstractConverter
     */
    public function setIgnoredResources($ignoredResources = [])
    {
        $this->ignoredResources = $ignoredResources;

        return $this;
    }

    /**
     * Convert the files.
     *
     * @return void
     */
    public function convert()
    {
        $baseFiles = $this->collectResourceNamesFromBaseLanguage();

        foreach ($this->collectLanguages() as $language) {
            $this->processLanguage($baseFiles, $language);
        }
    }

    /**
     * Determine all files contained in the base language.
     *
     * @return string[]
     */
    abstract protected function collectResourceNamesFromBaseLanguage();

    /**
     * Determine the languages from the existing directories in Xliff directory.
     *
     * @return string[]
     */
    abstract protected function collectLanguages();

    /**
     * Process a single language.
     *
     * @param string[] $resources The resource names to process.
     * @param string   $language  The language to process.
     *
     * @return void
     */
    abstract protected function processLanguage($resources, $language);

    /**
     * Cleanup files not needed in destination folder anymore.
     *
     * @param string   $directory     The language directory to clean.
     * @param string[] $keep          The files to keep.
     * @param string   $fileExtension The file extension of files to remove (.xlf or .php).
     *
     * @return void
     */
    protected function cleanupObsoleteFiles($directory, $keep, $fileExtension)
    {
        // If directory not exists, exit.
        if (!$this->cleanupObsolete || !is_dir($directory)) {
            return;
        }

        $finder = new Finder();
        $finder
            ->in($directory)
            ->name('*.' . $fileExtension);

        foreach ($keep as $resourceName) {
            $finder->notName($resourceName . $fileExtension);
        }

        // Also keep the ignored resources.
        foreach ($this->ignoredResources as $resourceName) {
            $finder->notName($resourceName . $fileExtension);
        }

        foreach ($finder as $file) {
            $this->logger->warning('Removing obsolete file {file}', ['file' => $file]);
        }

        // @codingStandardsIgnoreStart - Catch the error when directory is not empty.
        @rmdir($directory);
        // @codingStandardsIgnoreEnd
    }
}
