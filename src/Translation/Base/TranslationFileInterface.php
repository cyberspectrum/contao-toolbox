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

/**
 * This interface describes the fundamental basics of a translation file.
 */
interface TranslationFileInterface
{
    /**
     * Retrieve a list of all language keys.
     *
     * @return string[]
     */
    public function keys();

    /**
     * Search for an entry with the given id and remove it if found.
     *
     * @param string $key The language key to be searched.
     *
     * @return AbstractFile
     */
    public function remove($key);

    /**
     * Set a translation value.
     *
     * @param string $key   The translation key.
     *
     * @param string $value The value to set.
     *
     * @return AbstractFile
     */
    public function set($key, $value);

    /**
     * Retrieve a translation string.
     *
     * @param string $key The translation key.
     *
     * @return string
     */
    public function get($key);

    /**
     * Flag determining if the file contains changes.
     *
     * @return bool
     */
    public function isChanged();

    /**
     * Retrieve the language code of this file.
     *
     * @return mixed
     */
    public function getLanguageCode();
}
