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

namespace CyberSpectrum\Translation;

/**
 * This class provides an abstract base of a simple iterator over translation files.
 */
abstract class AbstractTranslationIterator implements \Iterator
{
    /**
     * The file we belong to.
     *
     * @var AbstractFile
     */
    protected $file;

    /**
     * The list of translation keys.
     *
     * @var string[]
     */
    protected $keys;

    /**
     * The current position in the file.
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Create a new instance.
     *
     * @param AbstractFile $file The Xliff file we are working on.
     */
    public function __construct(AbstractFile $file)
    {
        $this->position = 0;
        $this->file     = $file;
        $this->keys     = $file->getKeys();
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->keys[$this->position];
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return isset($this->keys[$this->position]);
    }
}
