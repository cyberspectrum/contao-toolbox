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

        $this->getNextToken();

        if (!$this->tokenIs('(')) {
            $this->bailUnexpectedToken('(');
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
            } else {
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

                    $this->getNextToken();


                    if ($this->tokenIs(T_ARRAY)) {
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

                    $this->popStack();
                } elseif ($this->tokenIs(',') || $this->tokenIs(')')) {
                // String item without key.
                    $this->debug('String item without key.');
                    $this->pushStack($this->counter++);
                    $this->parser->setValue($this->parser->getStack(), $key);
                    $this->popStack();
                }

                if ($this->tokenIs(',')) {
                    $this->getNextToken();

                    continue;
                }
                if ($this->tokenIs(')')) {
                    $this->debug('closing bracket.');
                    $this->getNextToken();

                    break;
                }
            }
        }
        $this->debug(' - exit.');
    }
}
