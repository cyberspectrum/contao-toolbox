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
 * This class parses a string value.
 */
class StringValue extends AbstractParser
{
    /**
     * The values.
     *
     * @var string[]
     */
    private $data;

    /**
     * {@inheritDoc}
     */
    public function parse()
    {
        $this->debug(' - enter.');

        while (true) {
            if ($this->tokenIs(T_CONSTANT_ENCAPSED_STRING)) {
                $token        = $this->getToken();
                $this->data[] = stripslashes(substr($token[1], 1, -1));
                $this->getNextToken();
                continue;
            }
            if ($this->tokenIs(T_LNUMBER)) {
                $token        = $this->getToken();
                $this->data[] = strval($token[1]);
                $this->getNextToken();
                continue;
            }
            if (
                $this->tokenIs(';')
                || $this->tokenIs(',')
                || $this->tokenIs(')')
                || $this->tokenIs(']')
                || $this->tokenIs(T_DOUBLE_ARROW)
            ) {
                break;
            }

            $this->bailUnexpectedToken();
        }
        $this->debug(' - exit.');
    }

    /**
     * Retrieve the value of the string parser.
     *
     * @return null|string
     */
    public function getValue()
    {
        if (!(is_array($this->data) && count($this->data))) {
            return null;
        }

        return implode('', $this->data);
    }
}
