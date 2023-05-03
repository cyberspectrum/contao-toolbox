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
final class TranslationSync
{
    /**
     * The source file.
     */
    private TranslationFileInterface $source;

    /**
     * The destination file.
     */
    private TranslationFileInterface $destination;

    /**
     * The logger instance.
     */
    private DelegatingLogger $logger;

    /**
     * Create a new instance.
     *
     * @param TranslationFileInterface $source      The source file.
     * @param TranslationFileInterface $destination The destination file.
     * @param LoggerInterface|null     $logger      The logger to use.
     */
    public function __construct(
        TranslationFileInterface $source,
        TranslationFileInterface $destination,
        ?LoggerInterface $logger = null
    ) {
        $this->logger      = new DelegatingLogger($logger);
        $this->source      = $source;
        $this->destination = $destination;
    }

    /**
     * Synchronize the contents of two translation files and return if the content has been changed.
     */
    public function sync(): bool
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
     */
    public function cleanUp(): bool
    {
        $removed = false;
        foreach (array_diff($this->destination->keys(), $this->source->keys()) as $key) {
            $this->logger->info('Removing orphan key <info>{key}</info>.', ['key' => $key]);
            $this->destination->remove($key);
            $removed = true;
        }
        return $removed;
    }

    /**
     * Synchronize the destination file with the contents from the source.
     *
     * @param TranslationFileInterface $source      The source file.
     * @param TranslationFileInterface $destination The destination file.
     * @param bool                     $cleanUp     Flag if orphan keys shall be removed from the destination.
     * @param LoggerInterface|null     $logger      The logger to use.
     */
    public static function syncFrom(
        TranslationFileInterface $source,
        TranslationFileInterface $destination,
        bool $cleanUp = true,
        ?LoggerInterface $logger = null
    ): bool {
        $sync   = new self($source, $destination, $logger);
        $result = false;
        if ($cleanUp) {
            $result = $sync->cleanUp();
        }

        return $sync->sync() || $result;
    }
}
