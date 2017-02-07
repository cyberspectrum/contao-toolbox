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

namespace CyberSpectrum\ContaoToolBox\Translation\Contao;

/**
 * This interface describes generic parsers.
 */
interface ParserInterface
{
    /**
     * Pass a debug message to the parenting parser.
     *
     * @param string $message The debug message.
     *
     * @return void
     */
    public function debug($message);

    /**
     * Push a value on the stack of the parenting parser.
     *
     * @param mixed $value The value to push on the stack.
     *
     * @return void
     */
    public function pushStack($value);

    /**
     * Pop a value from the stack of the parenting parser.
     *
     * @return mixed
     */
    public function popStack();

    /**
     * Reset the stack of the parenting parser.
     *
     * @return void
     */
    public function resetStack();

    /**
     * Check whether the current token matches the given value.
     *
     * @param mixed $type The type that is expected, either a string value or a tokenizer id.
     *
     * @return bool
     */
    public function tokenIs($type);

    /**
     * Bail with an unexpected token message.
     *
     * @param bool|false $expected The optionally expected tokens.
     *
     * @return void
     */
    public function bailUnexpectedToken($expected = false);

    /**
     * Fetch the next token.
     *
     * @return mixed
     */
    public function getToken();

    /**
     * Fetch the next token.
     *
     * @param bool|int $searchFor The token type to search for.
     *
     * @return mixed
     */
    public function getNextToken($searchFor = false);

    /**
     * Start the parsing.
     *
     * @return void
     */
    public function parse();
}
