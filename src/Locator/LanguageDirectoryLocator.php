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
     * @param array $filter The files to be filtered away (to be ignored).
     *
     * @return string[]
     *
     * @throws \InvalidArgumentException When the given source directory does not exist, an exception is thrown.
     */
    public function determineLanguages($filter = [])
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

            if ((strlen($item) == 2) && ((!$filter) || in_array($item, $filter))) {
                $matches[] = $item;
                $this->logger->info('using {dir}', ['dir' => $item]);
            } else {
                $this->logger->info('using {dir}', ['dir' => $item]);
            }
            $iterator->next();
        } while ($iterator->valid());

        return $matches;
    }
}
