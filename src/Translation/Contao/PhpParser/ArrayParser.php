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
class ArrayParser extends AbstractParser
{
    /** The counter. */
    private int $counter;

    public function __construct(Parser $parser, int $level = 0)
    {
        parent::__construct($parser, $level);

        $this->counter = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function parse(): void
    {
        if ($this->tokenIs(T_ARRAY)) {
            $this->getNextToken();

            if (!$this->tokenIs('(')) {
                $this->bailUnexpectedToken('(');
            }
        }

        $this->getNextToken();

        while (true) {
            // Sub array without key.
            if ($this->tokenIs(T_ARRAY)) {
                $this->debug('Sub array without key.');
                $this->pushStack((string) ($this->counter++));

                $subparser = new ArrayParser($this->parser, ($this->level + 1));
                $subparser->parse();

                $this->popStack();

                if ($this->tokenIs(',')) {
                    $this->getNextToken();

                    continue;
                }
                if ($this->tokenIs(')')) {
                    $this->getNextToken();

                    break;
                }

                continue;
            }

            if (!$this->parseItem()) {
                break;
            }
        }
    }

    /**
     * Parse an array value.
     *
     * Returns true if the parsing shall continue or false if the parsing is done.
     */
    private function parseItem(): bool
    {
        $subparser = new StringValueParser($this->parser, ($this->level + 1));
        $subparser->parse();

        $key = $subparser->getValue();

        if ($this->tokenIs(T_DOUBLE_ARROW)) {
            // We MUST have a key when double arrow is encountered.
            if (null === $key) {
                $this->bailUnexpectedToken();
            }
            $this->debug('key: ' . var_export($key, true));

            $this->pushStack($key);
            $this->parseValue();
            $this->popStack();
        } elseif ($this->tokenIsAnyOf(',', ')', ']')) {
            if (!is_string($key)) {
                $this->bailUnexpectedToken();
            }
            // String item without key.
            $this->debug('String item without key.');
            $this->pushStack((string) ($this->counter++));
            $this->parser->setValue($this->parser->getStack(), $key);
            $this->popStack();
        }

        if ($this->tokenIs(',')) {
            $this->getNextToken();
            return true;
        }
        if ($this->tokenIsAnyOf(')', ']')) {
            $this->getNextToken();

            return false;
        }

        return true;
    }

    /** Parse the value portion of a key => value array element. */
    private function parseValue(): void
    {
        $this->getNextToken();

        if ($this->tokenIsAnyOf(T_ARRAY, '[')) {
            // Sub array with key.
            $this->debug('Sub array with key.');
            $subparser = new ArrayParser($this->parser, ($this->level + 1));
            $subparser->parse();
        } else {
            // String item with key.
            $this->debug('String item with key.');
            $subparser = new StringValueParser($this->parser, ($this->level + 1));
            $subparser->parse();
            $value = $subparser->getValue();
            if (!is_string($value)) {
                $this->bailUnexpectedToken();
            }

            $this->parser->setValue($this->parser->getStack(), $value);
        }
    }
}
