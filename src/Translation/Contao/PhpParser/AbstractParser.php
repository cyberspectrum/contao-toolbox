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
    protected $parser;

    /**
     * The depth level.
     *
     * @var int
     */
    protected $level;

    /**
     * Create a new parser instance.
     *
     * @param Parser $parser The parenting parser instance.
     *
     * @param int    $level  The depth level this parser is in.
     */
    public function __construct(Parser $parser, $level = 0)
    {
        $this->parser = $parser;
        $this->level  = $level;
    }

    /**
     * Pass a debug message to the parenting parser.
     *
     * @param string $message The debug message.
     *
     * @return void
     */
    protected function debug($message)
    {
        $this->parser->debug(__CLASS__ . ' ' . $this->level . ' ' . $message);
    }

    /**
     * {@inheritDoc}
     */
    public function pushStack($value)
    {
        $this->parser->pushStack($value);
    }

    /**
     * {@inheritDoc}
     */
    public function popStack()
    {
        return $this->parser->popStack();
    }

    /**
     * {@inheritDoc}
     */
    public function resetStack()
    {
        $this->parser->resetStack();
    }

    /**
     * {@inheritDoc}
     */
    public function tokenIs($type)
    {
        return $this->parser->tokenIs($type);
    }

    /**
     * {@inheritDoc}
     */
    public function bailUnexpectedToken($expected = false)
    {
        $this->parser->bailUnexpectedToken($expected);
    }

    /**
     * {@inheritDoc}
     */
    public function getToken()
    {
        return $this->parser->getToken();
    }

    /**
     * {@inheritDoc}
     */
    public function getNextToken($searchfor = false)
    {
        $this->parser->getNextToken($searchfor);
    }
}
