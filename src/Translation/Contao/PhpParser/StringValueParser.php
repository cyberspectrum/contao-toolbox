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
 * This class parses a string value.
 */
class StringValueParser extends AbstractParser
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
        while (true) {
            // String concatenation.
            if ($this->isIgnoredToken()) {
                $this->getNextToken();
                continue;
            }
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
            if ($this->tokenIs(T_STRING)) {
                $token = $this->getToken();
                if ('null' !== strtolower($token[1])) {
                    $this->bailUnexpectedToken();
                }
                $this->data[] = null;
                $this->getNextToken();
                continue;
            }

            if ($this->isEndToken()) {
                break;
            }

            $this->bailUnexpectedToken();
        }
    }

    /**
     * Check if the current token is any of the ignored tokens.
     *
     * @return bool
     */
    private function isIgnoredToken()
    {
        return $this->tokenIs('.') || $this->tokenIs(T_COMMENT) || $this->tokenIs(T_DOC_COMMENT);
    }

    /**
     * Check if the current token is a ending token.
     *
     * @return bool
     */
    private function isEndToken()
    {
        return $this->tokenIs(';')
        || $this->tokenIs(',')
        || $this->tokenIs(')')
        || $this->tokenIs(']')
        || $this->tokenIs(T_DOUBLE_ARROW);
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
