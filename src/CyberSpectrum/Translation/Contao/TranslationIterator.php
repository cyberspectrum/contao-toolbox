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

use CyberSpectrum\Translation\AbstractTranslationIterator;

/**
 * This class provides an iterator over all language strings in a Contao File.
 */
class TranslationIterator extends AbstractTranslationIterator
{
    /**
     * The file being iterated.
     *
     * @var ContaoFile
     */
    protected $file;

    /**
     * Create a new instance.
     *
     * @param ContaoFile $file The Contao file we are working on.
     */
    // @codingStandardsIgnoreStart - Method override is not useless, we change the parameter type.
    public function __construct(ContaoFile $file)
    {
        parent::__construct($file);
    }
    // @codingStandardsIgnoreEnd

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return new \CyberSpectrum\Translation\Contao\TranslationEntry($this->key(), $this->file);
    }
}
