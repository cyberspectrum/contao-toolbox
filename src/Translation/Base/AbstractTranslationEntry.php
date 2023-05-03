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

use RuntimeException;

/**
 * This class represents an abstract translation entry.
 *
 * @template TDoc of TranslationFileInterface
 */
abstract class AbstractTranslationEntry
{
    /**
     * The document we belong to.
     *
     * @var TDoc
     */
    protected TranslationFileInterface $doc;

    /**
     * The translation key for this entry.
     *
     * @var string
     */
    protected string $key;

    /**
     * Create a new instance.
     *
     * @param string $key The translation key.
     * @param TDoc   $doc The document this entry belongs to.
     *
     * @throws RuntimeException When the key is empty.
     */
    public function __construct(string $key, TranslationFileInterface $doc)
    {
        if ('' === $key) {
            throw new RuntimeException('Empty Id passed.');
        }

        $this->key = $key;
        $this->doc = $doc;
    }

    /**
     * Retrieves the key from this entry.
     */
    public function getKey(): string
    {
        return $this->key;
    }
}
