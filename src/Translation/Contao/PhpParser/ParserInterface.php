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

namespace CyberSpectrum\ContaoToolBox\Translation\Contao\PhpParser;

use RuntimeException;

/**
 * This interface describes generic parsers.
 *
 * @psalm-type TToken=string|array{0: int, 1: string, 2: int}
 */
interface ParserInterface
{
    /**
     * Push a value on the stack of the parenting parser.
     *
     * @param TToken|string $value The value to push on the stack.
     */
    public function pushStack(array|string $value): void;

    /**
     * Pop a value from the stack of the parenting parser.
     */
    public function popStack(): mixed;

    /**
     * Reset the stack of the parenting parser.
     */
    public function resetStack(): void;

    /**
     * Check whether the current token matches the given value.
     *
     * @param string|int $type The type that is expected, either a string value or a tokenizer id.
     */
    public function tokenIs(string|int $type): bool;

    /**
     * Check whether the current token matches the given value.
     *
     * @param string|int ...$types The type that is expected, either a string value or a tokenizer id.
     */
    public function tokenIsAnyOf(string|int ...$types): bool;

    /**
     * Bail with an unexpected token message.
     *
     * @param false|int|string $expected The optionally expected token type.
     *
     * @throws RuntimeException With the unexpected token in the message.
     */
    public function bailUnexpectedToken(false|int|string $expected = false): never;

    /**
     * Fetch the next token.
     *
     * @return null|TToken
     */
    public function getToken(): null|string|array;

    /**
     * Fetch the next token.
     *
     * @param false|int|string $searchFor The token type to search for.
     */
    public function getNextToken(false|int|string $searchFor = false): void;

    /**
     * Start the parsing.
     */
    public function parse(): void;
}
