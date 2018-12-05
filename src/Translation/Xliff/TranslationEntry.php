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

namespace CyberSpectrum\ContaoToolBox\Translation\Xliff;

use CyberSpectrum\ContaoToolBox\Translation\Base\AbstractTranslationEntry;

/**
 * This class represents a translation entry in a XLIFF file.
 *
 * @property XliffFile $doc Defined in parent class.
 */
class TranslationEntry extends AbstractTranslationEntry
{
    /**
     * Create a new instance.
     *
     * @param string $key The translation key.
     *
     * @param XliffFile   $doc The document this entry belongs to.
     *
     * @throws \Exception When the key is empty.
     */
    // @codingStandardsIgnoreStart - Method override is not useless, we change the parameter type.
    public function __construct($key, XliffFile $doc)
    {
        parent::__construct($key, $doc);
    }
    // @codingStandardsIgnoreEnd

    /**
     * Set the source value.
     *
     * @param string $value The value to set.
     *
     * @return TranslationEntry
     */
    public function setSource($value)
    {
        $mode = $this->doc->getMode();
        $this->doc->setMode('source');
        $this->doc->set($this->getKey(), $value);
        $this->doc->setMode($mode);

        return $this;
    }

    /**
     * Fetches the source value from this entry.
     *
     * @return null|string
     */
    public function getSource()
    {
        $mode = $this->doc->getMode();
        $this->doc->setMode('source');
        $value = $this->doc->get($this->getKey());
        $this->doc->setMode($mode);

        return $value;
    }

    /**
     * Set the target value.
     *
     * @param string $value The value to set.
     *
     * @return TranslationEntry
     */
    public function setTarget($value)
    {
        $mode = $this->doc->getMode();
        $this->doc->setMode('target');
        $this->doc->set($this->getKey(), $value);
        $this->doc->setMode($mode);

        return $this;
    }

    /**
     * Fetches the target value from this entry.
     *
     * @return null|string
     */
    public function getTarget()
    {
        $mode = $this->doc->getMode();
        $this->doc->setMode('target');
        $value = $this->doc->get($this->getKey());
        $this->doc->setMode($mode);

        return $value;
    }
}
