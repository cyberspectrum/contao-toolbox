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
 * This class provides an abstract base implementation of translation files.
 */
abstract class AbstractFile implements \IteratorAggregate
{
    /**
     * Retrieve a list of all language keys.
     *
     * @return array
     */
    abstract public function getKeys();
}
