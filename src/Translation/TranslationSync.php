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

namespace CyberSpectrum\ContaoToolBox\Translation;

use CyberSpectrum\ContaoToolBox\Translation\Base\TranslationFileInterface;
use CyberSpectrum\ContaoToolBox\Util\DelegatingLogger;
use Psr\Log\LoggerInterface;

/**
 * This class takes care of synching one file with another.
 */
class TranslationSync
{
    /**
     * The source file.
     *
     * @var TranslationFileInterface
     */
    private $source;

    /**
     * The destination file.
     *
     * @var TranslationFileInterface
     */
    private $destination;

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Create a new instance.
     *
     * @param TranslationFileInterface $source      The source file.
     * @param TranslationFileInterface $destination The destination file.
     * @param LoggerInterface          $logger      The logger to use.
     */
    public function __construct(
        TranslationFileInterface $source,
        TranslationFileInterface $destination,
        LoggerInterface $logger = null
    ) {
        $this->logger      = new DelegatingLogger($logger);
        $this->source      = $source;
        $this->destination = $destination;
    }

    /**
     * Synchronize the contents of two translation files and return if the content has been changed.
     *
     * @return bool
     */
    public function sync()
    {
        $changed = false;

        foreach ($this->source->keys() as $key) {
            if ($this->destination->get($key) !== ($value = $this->source->get($key))) {
                $changed = true;
                $this->logger->info('Updating key <info>{key}</info>.', ['key' => $key]);
                if (null === $value) {
                    $this->destination->remove($key);
                    continue;
                }
                $this->destination->set($key, $value);
            }
        }

        return $changed;
    }

    /**
     * Remove all keys in destination that are not present in source.
     *
     * @return void
     */
    public function cleanUp()
    {
        foreach (array_diff($this->destination->keys(), $this->source->keys()) as $key) {
            $this->logger->info('Removing orphan key <info>{key}</info>.', ['key' => $key]);
            $this->destination->remove($key);
        }
    }

    /**
     * Synchronize the destination file with the contents from the source.
     *
     * @param TranslationFileInterface $source      The source file.
     * @param TranslationFileInterface $destination The destination file.
     * @param bool                     $cleanUp     Flag if orphan keys shall be removed from the destination.
     * @param LoggerInterface          $logger      The logger to use.
     *
     * @return mixed
     */
    public static function syncFrom(
        TranslationFileInterface $source,
        TranslationFileInterface $destination,
        $cleanUp = true,
        LoggerInterface $logger = null
    ) {
        $sync = new static($source, $destination, $logger);
        if ($cleanUp) {
            $sync->cleanUp();
        }

        return $sync->sync();
    }
}
