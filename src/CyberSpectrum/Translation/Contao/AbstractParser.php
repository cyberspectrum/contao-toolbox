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
     * {@inheritDoc}
     */
    public function debug($message)
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
