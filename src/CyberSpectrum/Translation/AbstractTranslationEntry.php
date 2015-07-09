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

namespace CyberSpectrum\Translation;

/**
 * This class represents an abstract translation entry.
 */
abstract class AbstractTranslationEntry
{
    /**
     * The document we are belonging to.
     *
     * @var AbstractFile
     */
    protected $doc;

    /**
     * The translation key for this entry.
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new instance.
     *
     * @param string       $key The translation key.
     *
     * @param AbstractFile $doc The document this entry belongs to.
     *
     * @throws \Exception When the key is empty.
     */
    public function __construct($key, AbstractFile $doc)
    {
        if (!strlen($key)) {
            throw new \Exception('Empty Id passed.');
        }

        $this->key = $key;
        $this->doc = $doc;
    }

    /**
     * Retrieves the key from this entry.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
