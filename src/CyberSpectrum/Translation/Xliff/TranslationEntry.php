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

namespace CyberSpectrum\Translation\Xliff;

use CyberSpectrum\Translation\AbstractTranslationEntry;

/**
 * This class represents a translation entry in a XLIFF file.
 */
class TranslationEntry extends AbstractTranslationEntry
{
    /**
     * The document we are belonging to.
     *
     * @var XliffFile
     */
    protected $doc;

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
        $this->doc->setSource($this->getKey(), $value);

        return $this;
    }

    /**
     * Fetches the source value from this entry.
     *
     * @return null|string
     */
    public function getSource()
    {
        return $this->doc->getSource($this->getKey());
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
        $this->doc->setTarget($this->getKey(), $value);

        return $this;
    }

    /**
     * Fetches the target value from this entry.
     *
     * @return null|string
     */
    public function getTarget()
    {
        return $this->doc->getTarget($this->getKey());
    }
}
