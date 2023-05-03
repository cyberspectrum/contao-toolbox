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

/**
 * This class implements a simple PHP language array parser.
 */
abstract class AbstractParser implements ParserInterface
{
    /**
     * The parser instance.
     *
     * @var Parser
     */
    protected Parser $parser;

    /**
     * The depth level.
     *
     * @var int
     */
    protected int $level;

    /**
     * Create a new parser instance.
     *
     * @param Parser $parser The parenting parser instance.
     * @param int    $level  The depth level this parser is in.
     */
    public function __construct(Parser $parser, int $level = 0)
    {
        $this->parser = $parser;
        $this->level  = $level;
    }

    /**
     * Pass a debug message to the parenting parser.
     *
     * @param string $message The debug message.
     */
    protected function debug(string $message): void
    {
        $this->parser->debug(static::class . ' ' . $this->level . ' ' . $message);
    }

    public function pushStack(array|string $value): void
    {
        $this->parser->pushStack($value);
    }

    public function popStack(): mixed
    {
        return $this->parser->popStack();
    }

    public function resetStack(): void
    {
        $this->parser->resetStack();
    }

    public function tokenIs(string|int $type): bool
    {
        return $this->parser->tokenIs($type);
    }

    public function tokenIsAnyOf(string|int ...$types): bool
    {
        return $this->parser->tokenIsAnyOf(...$types);
    }

    public function bailUnexpectedToken(false|int|string $expected = false): never
    {
        $this->parser->bailUnexpectedToken($expected);
    }

    public function getToken(): null|string|array
    {
        return $this->parser->getToken();
    }

    public function getNextToken(false|int|string $searchFor = false): void
    {
        $this->parser->getNextToken($searchFor);
    }
}
