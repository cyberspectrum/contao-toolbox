<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Translation\Contao;

use CyberSpectrum\Translation\AbstractTranslationEntry;

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
        $this->doc->setValue($this->getKey(), $value);

        return $this;
    }

    /**
     * Fetches the value from this entry.
     *
     * @return null|string
     */
    public function getValue()
    {
        return $this->doc->getValue($this->getKey());
    }
}
