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
    /**
     * The counter.
     *
     * @var int
     */
    private $counter;

    /**
     * {@inheritDoc}
     */
    public function __construct(Parser $parser, $level = 0)
    {
        parent::__construct($parser, $level);

        $this->counter = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function parse()
    {
        $this->debug(' - enter.');

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
                $this->pushStack($this->counter++);

                $subparser = new ArrayParser($this->parser, ($this->level + 1));
                $subparser->parse();

                $this->popStack();

                if ($this->tokenIs(',')) {
                    $this->getNextToken();

                    continue;
                }
                if ($this->tokenIs(')')) {
                    $this->debug('closing bracket.');
                    $this->getNextToken();

                    break;
                }

                continue;
            }

            if (!$this->parseItem()) {
                break;
            }
        }
        $this->debug(' - exit.');
    }

    /**
     * Parse an array value.
     *
     * Returns true if the parsing shall continue or false if the parsing is done.
     *
     * @return bool
     */
    private function parseItem()
    {
        $subparser = new StringValue($this->parser, ($this->level + 1));
        $subparser->parse();

        $key = $subparser->getValue();

        $this->debug('key: ' . var_export($key, true));

        if ($this->tokenIs(T_DOUBLE_ARROW)) {
            // We MUST have an key when double arrow is encountered.
            if ($key === null) {
                $this->bailUnexpectedToken();
            }

            $this->pushStack($key);
            $this->parseValue();
            $this->popStack();
        } elseif ($this->tokenIs(',') || $this->tokenIs(')') || $this->tokenIs(']')) {
            // String item without key.
            $this->debug('String item without key.');
            $this->pushStack($this->counter++);
            $this->parser->setValue($this->parser->getStack(), $key);
            $this->popStack();
        }

        if ($this->tokenIs(',')) {
            $this->getNextToken();
            return true;
        }
        if ($this->tokenIs(')') || $this->tokenIs(']')) {
            $this->debug('closing bracket.');
            $this->getNextToken();

            return false;
        }

        return true;
    }

    /**
     * Parse the value portion of a key => value array element.
     *
     * @return void
     */
    private function parseValue()
    {
        $this->getNextToken();

        if ($this->tokenIs(T_ARRAY) || $this->tokenIs('[')) {
            // Sub array with key.
            $this->debug('Sub array with key.');
            $subparser = new ArrayParser($this->parser, ($this->level + 1));
            $subparser->parse();
        } else {
            // String item with key.
            $this->debug('String item with key.');
            $subparser = new StringValue($this->parser, ($this->level + 1));
            $subparser->parse();

            $this->parser->setValue($this->parser->getStack(), $subparser->getValue());
        }
    }
}
