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

namespace CyberSpectrum\ContaoToolBox\Translation\Base;

use CyberSpectrum\ContaoToolBox\Util\DelegatingLogger;
use Psr\Log\LoggerInterface;

/**
 * This class provides an abstract base implementation of translation files.
 */
abstract class AbstractFile implements \IteratorAggregate, TranslationFileInterface
{
    /**
     * Debug flag.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Create a new instance.
     *
     * @param LoggerInterface $logger The logger to use.
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = new DelegatingLogger($logger);
    }

    /**
     * Retrieve a list of all language keys.
     *
     * @return array
     *
     * @deprecated
     */
    public function getKeys()
    {
        return $this->keys();
    }
}
