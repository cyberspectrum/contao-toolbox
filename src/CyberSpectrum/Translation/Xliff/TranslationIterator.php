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

use CyberSpectrum\Translation\AbstractTranslationIterator;

/**
 * This class provides a simple iterator over XLIFF files.
 */
class TranslationIterator extends AbstractTranslationIterator
{
    /**
     * The file we belong to.
     *
     * @var XliffFile
     */
    protected $file;

    /**
     * Create a new instance.
     *
     * @param XliffFile $file The Xliff file we are working on.
     */
    // @codingStandardsIgnoreStart - Method override is not useless, we change the parameter type.
    public function __construct(XliffFile $file)
    {
        parent::__construct($file);
    }
    // @codingStandardsIgnoreEnd

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return new \CyberSpectrum\Translation\Xliff\TranslationEntry($this->key(), $this->file);
    }
}
