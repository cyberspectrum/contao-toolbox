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

namespace CyberSpectrum\ContaoToolBox\Locator;

use CyberSpectrum\ContaoToolBox\Util\DelegatingLogger;
use Psr\Log\LoggerInterface;

/**
 * This class locates language directories within a base directory.
 */
class LanguageDirectoryLocator
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The base directory.
     *
     * @var string
     */
    private $baseDir;

    /**
     * Create a new instance.
     *
     * @param string          $baseDir The base Directory.
     *
     * @param LoggerInterface $logger  The logger to use.
     */
    public function __construct($baseDir, LoggerInterface $logger = null)
    {
        $this->logger  = new DelegatingLogger($logger);
        $this->baseDir = $baseDir;
    }

    /**
     * Determine the list of languages.
     *
     * @param array $allowed The languages to be kept.
     *
     * @return string[]
     *
     * @throws \InvalidArgumentException When the given source directory does not exist, an exception is thrown.
     */
    public function determineLanguages($allowed = [])
    {
        if (!is_dir($this->baseDir)) {
            throw new \InvalidArgumentException(sprintf('The path %s does not exist.', $this->baseDir));
        }

        $this->logger->notice('scanning for languages in: {src-dir}', ['src-dir' => $this->baseDir]);

        $matches  = [];
        $iterator = new \DirectoryIterator($this->baseDir);
        do {
            $item = $iterator->getFilename();
            if ($iterator->isDot()) {
                $iterator->next();
                continue;
            }

            if ($this->isValidLanguageDirectory($item) && !$this->isFiltered($item, $allowed)) {
                $matches[] = $item;
                $this->logger->info('using {dir}', ['dir' => $item]);
            } else {
                $this->logger->info('not using {dir}', ['dir' => $item]);
            }
            $iterator->next();
        } while ($iterator->valid());

        return $matches;
    }

    /**
     * Test if the passed value is a valid handle for a language directory.
     *
     * @param string $dirName The name.
     *
     * @return bool
     */
    private function isValidLanguageDirectory($dirName)
    {
        return preg_match('#^[a-z]{2}([-_][a-zA-Z]{0,2})?$#', $dirName);
    }

    /**
     * Test if the passed name matches the filter.
     *
     * @param string   $dirName The directory name.
     *
     * @param string[] $filter  The filtered names.
     *
     * @return bool
     */
    private function isFiltered($dirName, $filter)
    {
        if (empty($filter)) {
            return false;
        }

        return !in_array($dirName, $filter);
    }
}
