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

namespace CyberSpectrum\ContaoToolBox\Translation\Contao;

use CyberSpectrum\ContaoToolBox\Translation\Base\AbstractTranslationEntry;

/**
 * This class encapsulates an Contao File translation entry.
 */
class TranslationEntry extends AbstractTranslationEntry
{
    /**
     * The document we are belonging to.
     *
     * @var ContaoFile
     */
    protected $doc;

    /**
     * Create a new instance.
     *
     * @param string     $key The translation key.
     *
     * @param ContaoFile $doc The document this entry belongs to.
     *
     * @throws \Exception When the key is empty.
     */
    // @codingStandardsIgnoreStart - Method override is not useless, we change the parameter type.
    public function __construct($key, ContaoFile $doc)
    {
        parent::__construct($key, $doc);
    }
    // @codingStandardsIgnoreEnd

    /**
     * Set the value.
     *
     * @param string $value The value to set.
     *
     * @return TranslationEntry
     */
    public function setValue($value)
    {
        $this->doc->set($this->getKey(), $value);

        return $this;
    }

    /**
     * Fetches the value from this entry.
     *
     * @return null|string
     */
    public function getValue()
    {
        return $this->doc->get($this->getKey());
    }
}
