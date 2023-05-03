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

use CyberSpectrum\ContaoToolBox\Translation\Base\AbstractTranslationIterator;

/**
 * This class provides a simple iterator over XLIFF files.
 *
 * @template-extends AbstractTranslationIterator<TranslationEntry, XliffFile>
 */
class TranslationIterator extends AbstractTranslationIterator
{
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

    public function current(): TranslationEntry
    {
        return new TranslationEntry($this->key(), $this->file);
    }
}
