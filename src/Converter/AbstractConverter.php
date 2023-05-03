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

use function is_dir;
use function rmdir;

/**
 * This class is the abstract base for the converters.
 */
abstract class AbstractConverter
{
    /** The logger to use. */
    protected LoggerInterface $logger;

    /** The Contao language directory. */
    protected string $contaoPath;

    /** The Xliff language directory. */
    protected string $xliffPath;

    /** The defined base language. */
    protected string $baseLanguage;

    /**
     * Optional whitelist for languages.
     *
     * @var list<string>
     */
    protected array $onlyLanguages = [];

    /**
     * List of ignored files.
     *
     * @var list<string>
     */
    protected array $ignoredResources = [];

    /** Flag if obsolete resources shall be removed. */
    private bool $cleanupObsolete = false;

    /**
     * Create a new instance.
     *
     * @param string          $contaoPath   The root directory for the Contao languages.
     * @param string          $xliffPath    The root directory for the Xliff languages.
     * @param string          $baseLanguage The base language.
     * @param LoggerInterface $logger       The logger to use.
     */
    public function __construct(string $contaoPath, string $xliffPath, string $baseLanguage, LoggerInterface $logger)
    {
        $this->contaoPath   = $contaoPath;
        $this->xliffPath    = $xliffPath;
        $this->baseLanguage = $baseLanguage;
        $this->logger       = $logger;
    }

    /**
     * Set language whitelist to only convert certain languages.
     *
     * @param list<string> $onlyLanguages The new value, pass empty array to clear whitelist.
     */
    public function setOnlyLanguages(array $onlyLanguages = []): self
    {
        $this->onlyLanguages = $onlyLanguages;

        return $this;
    }

    /**
     * Set cleanup obsolete flag.
     *
     * @param bool $cleanupObsolete The value.
     */
    public function setCleanupObsolete(bool $cleanupObsolete = true): self
    {
        $this->cleanupObsolete = $cleanupObsolete;

        return $this;
    }

    /**
     * Set resource blacklist to skip converting certain resources.
     *
     * @param list<string> $ignoredResources The new value, pass empty array to clear blacklist.
     *
     * @return AbstractConverter
     */
    public function setIgnoredResources(array $ignoredResources = []): self
    {
        $this->ignoredResources = $ignoredResources;

        return $this;
    }

    /** Convert the files. */
    public function convert(): void
    {
        $baseFiles = $this->collectResourceNamesFromBaseLanguage();

        foreach ($this->collectLanguages() as $language) {
            $this->processLanguage($baseFiles, $language);
        }
    }

    /**
     * Determine all files contained in the base language.
     *
     * @return list<string>
     */
    abstract protected function collectResourceNamesFromBaseLanguage(): array;

    /**
     * Determine the languages from the existing directories in Xliff directory.
     *
     * @return list<string>
     */
    abstract protected function collectLanguages(): array;

    /**
     * Process a single language.
     *
     * @param list<string> $resources The resource names to process.
     * @param string       $language  The language to process.
     */
    abstract protected function processLanguage(array $resources, string $language): void;

    /**
     * Cleanup files not needed in destination folder anymore.
     *
     * @param string       $directory     The language directory to clean.
     * @param list<string> $keep          The files to keep.
     * @param string       $fileExtension The file extension of files to remove (.xlf or .php).
     */
    protected function cleanupObsoleteFiles(string $directory, array $keep, string $fileExtension): void
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
