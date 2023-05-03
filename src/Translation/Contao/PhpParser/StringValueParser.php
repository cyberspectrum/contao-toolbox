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
final class StringValueParser extends AbstractParser
{
    /**
     * The values.
     *
     * @var list<string|null>
     */
    private array $data = [];

    public function parse(): void
    {
        while (true) {
            switch (true) {
                // String concatenation.
                case $this->isIgnoredToken():
                    $this->getNextToken();
                    break;
                case $this->tokenIs(T_CONSTANT_ENCAPSED_STRING):
                    $token = $this->getToken();
                    assert(is_array($token));
                    $this->data[] = stripslashes(substr($token[1], 1, -1));
                    $this->getNextToken();
                    break;
                case $this->tokenIs(T_LNUMBER):
                    $token = $this->getToken();
                    assert(is_array($token));
                    $this->data[] = $token[1];
                    $this->getNextToken();
                    break;
                case $this->tokenIs(T_STRING):
                    $token = $this->getToken();
                    assert(is_array($token));
                    if ('null' !== strtolower($token[1])) {
                        $this->bailUnexpectedToken();
                    }
                    $this->data[] = null;
                    $this->getNextToken();
                    break;
                case $this->isEndToken():
                    break 2;
                default:
                    $this->bailUnexpectedToken();
            }
        }
    }

    /**
     * Retrieve the value of the string parser.
     */
    public function getValue(): ?string
    {
        if ([] === $this->data) {
            return null;
        }

        return implode('', $this->data);
    }

    /**
     * Check if the current token is any of the ignored tokens.
     */
    private function isIgnoredToken(): bool
    {
        return $this->tokenIsAnyOf('.', T_COMMENT, T_DOC_COMMENT);
    }

    /**
     * Check if the current token is an ending token.
     */
    private function isEndToken(): bool
    {
        return $this->tokenIsAnyOf(';', ',', ')', ']', T_DOUBLE_ARROW);
    }
}
