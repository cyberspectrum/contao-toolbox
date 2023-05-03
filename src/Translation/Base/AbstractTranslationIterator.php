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

use Iterator;

/**
 * This class provides an abstract base of a simple iterator over translation files.
 *
 * @template TValue
 * @template-covariant TFile of TranslationFileInterface
 *
 * @implements Iterator<string, TValue>
 */
abstract class AbstractTranslationIterator implements Iterator
{
    /**
     * The file we belong to.
     *
     * @var TFile
     */
    protected TranslationFileInterface $file;

    /**
     * The list of translation keys.
     *
     * @var list<string>
     */
    protected array $keys;

    /**
     * The current position in the file.
     */
    protected int $position = 0;

    /**
     * Create a new instance.
     *
     * @param TFile $file The file we are working on.
     */
    public function __construct(TranslationFileInterface $file)
    {
        $this->position = 0;
        $this->file     = $file;
        $this->keys     = $file->keys();
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function key(): string
    {
        return $this->keys[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }
}
