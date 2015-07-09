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
