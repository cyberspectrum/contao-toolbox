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
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\ContaoToolBox\Translation\Contao\PhpParser;

use CyberSpectrum\ContaoToolBox\Translation\Contao\ContaoFile;
use Psr\Log\LoggerInterface;

/**
 * This class implements a generic parser.
 */
class Parser implements ParserInterface
{
    /**
     * The file being parsed.
     *
     * @var ContaoFile
     */
    private $file;

    /**
     * The logger to use.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The tokens contained in the parser.
     *
     * @var array
     */
    private $tokens;

    /**
     * The previous token.
     *
     * @var string|int|null|array
     */
    private $prevToken;

    /**
     * Index of the previous token.
     *
     * @var int
     */
    private $token;

    /**
     * The stack of language keys.
     *
     * @var string[]
     */
    private $keystack;

    /**
     * The auto index.
     *
     * @var array
     */
    private $autoIndex = array();

    /**
     * Create a new instance.
     *
     * @param ContaoFile      $file   The file to parse.
     *
     * @param LoggerInterface $logger The logger to use.
     */
    public function __construct(ContaoFile $file, LoggerInterface $logger)
    {
        $this->file   = $file;
        $this->logger = $logger;
    }

    /**
     * Send a debug message to the attached file.
     *
     * @param string $message The message to send.
     *
     * @return void
     */
    public function debug($message)
    {
        $this->logger->debug($message);
    }

    /**
     * {@inheritDoc}
     */
    public function pushStack($value)
    {
        if (is_array($value)) {
            if (T_CONSTANT_ENCAPSED_STRING === $value[0]) {
                $this->keystack[] = substr($value[1], 1, -1);
            } else {
                $this->keystack[] = strval($value[1]);
            }
        } else {
            $this->keystack[] = $value;
        }
        $this->debug('pushed stack ' . implode('.', $this->keystack));
    }

    /**
     * {@inheritDoc}
     */
    public function popStack()
    {
        $value = array_pop($this->keystack);
        $this->debug('popped stack ' . implode('.', $this->keystack));

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function resetStack()
    {
        $this->keystack = array();
        $this->debug('stack reset');
    }

    /**
     * Fetch the stack either as array or as dot separated string.
     *
     * @param bool|true $imploded If true, the result will be a dot separated string, the stack array otherwise.
     *
     * @return string|\string[]
     */
    public function getStack($imploded = true)
    {
        return (bool) $imploded ? implode('.', $this->keystack) : $this->keystack;
    }

    /**
     * Set a value.
     *
     * @param string $key   The key to set in the file.
     *
     * @param string $value The value to set.
     *
     * @return void
     */
    public function setValue($key, $value)
    {
        \Closure::bind(
            function ($key, $value) {
                $this->langstrings[$key] = $value;
            },
            $this->file,
            $this->file
        )->__invoke($key, $value);
    }

    /**
     * Read a language string from the file.
     *
     * @return void
     */
    private function readLangString()
    {
        while (!$this->tokenIs(';')) {
            $this->getNextToken();

            if ($this->tokenIs('[')) {
                $this->subParserSquareBracket();
                continue;
            }
            if ($this->tokenIs('=')) {
                // right hand of the assignment.
                $this->getNextToken();

                if ($this->tokenIs(T_ARRAY) || $this->tokenIs('[')) {
                    $arrayParser = new ArrayParser($this, 1);
                    $arrayParser->parse();
                } else {
                    $subparser = new StringValueParser($this);
                    $subparser->parse();

                    $this->setValue(implode('.', $this->keystack), $subparser->getValue());
                }

                continue;
            }
            if (!$this->tokenIs(']')) {
                $this->bailUnexpectedToken();
            }
        }

        if ($this->tokenIs(';')) {
            // Reset stack.
            $this->resetStack();
        }
    }

    /**
     * Spawn a sub parser to parse the [] expression.
     *
     * @return void
     */
    private function subParserSquareBracket()
    {
        $this->getNextToken();

        $subparser = new StringValueParser($this);
        $subparser->parse();

        if (null === $subparser->getValue()) {
            // auto indexed array
            if ($this->tokenIs(']')) {
                $path = implode('.', $this->keystack);

                if (!isset($this->autoIndex[$path])) {
                    $this->autoIndex[$path] = 0;
                } else {
                    $this->autoIndex[$path]++;
                }

                $this->pushStack($this->autoIndex[$path]);
            } else {
                // invalid code?!
                $this->bailUnexpectedToken();
            }
        } else {
            $this->pushStack($subparser->getValue());
        }
    }

    /**
     * Set the content.
     *
     * @param string $content The PHP text to parse.
     *
     * @return void
     */
    public function setContent($content)
    {
        $this->tokens = token_get_all($content);
    }

    /**
     * {@inheritDoc}
     */
    public function parse()
    {
        $this->getNextToken(T_VARIABLE);
        while ($this->token) {
            if ((T_VARIABLE === $this->token[0]) && '$GLOBALS' === $this->token[1]) {
                $this->getNextToken();
                if ($this->tokenIs('[')) {
                    $this->getNextToken();

                    $this->checkIsNotString(T_CONSTANT_ENCAPSED_STRING);
                    // Wrong sub array.
                    if ('TL_LANG' !== substr($this->token[1], 1, -1)) {
                        $this->bailUnexpectedToken(T_CONSTANT_ENCAPSED_STRING);
                    }

                    $this->readLangString();
                } else {
                    $this->bailUnexpectedToken('[');
                }
            }
            $this->getNextToken(T_VARIABLE);
        }
    }

    /**
     * Ensure that the current token is a string.
     *
     * @param mixed      $value    The Value that the current token shall have.
     *
     * @param bool|mixed $expected The expected token type/value or false if unknown which one was expected.
     *
     * @return void
     */
    public function checkIsString($value = false, $expected = false)
    {
        if (!is_string($this->token) || ($value && ($this->token !== $value))) {
            $this->bailUnexpectedToken($expected);
        }
    }

    /**
     * Ensure the current token is not a string and optionally is of the given value.
     *
     * @param mixed      $value    Optional the value.
     *
     * @param bool|mixed $expected The expected token type/value or false if unknown which one was expected.
     *
     * @return void
     */
    public function checkIsNotString($value = false, $expected = false)
    {
        if (is_string($this->token) || ($value && ($this->token[0] !== $value))) {
            $this->bailUnexpectedToken($expected);
        }
    }

    /**
     * Check whether the current token matches the given value.
     *
     * @param mixed $type The type that is expected, either a string value or a tokenizer id.
     *
     * @return bool
     */
    public function tokenIs($type)
    {
        if (is_string($this->token)) {
            return ($this->token === $type);
        }

        return ($this->token[0] === $type);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException With the unexpected token in the message.
     */
    public function bailUnexpectedToken($expected = false)
    {
        if (is_array($this->token)) {
            if ($expected) {
                throw new \RuntimeException(
                    sprintf(
                        'Unexpected token %s detected at position %d - value: %s, expected %s',
                        token_name($this->token[0]),
                        $this->token[2],
                        $this->token[1],
                        token_name($expected)
                    )
                );
            }

            throw new \RuntimeException(
                sprintf(
                    'Unexpected token %s detected at position %d - value: %s',
                    token_name($this->token[0]),
                    $this->token[2],
                    $this->token[1]
                )
            );
        }

        throw new \RuntimeException(sprintf('Unexpected token %s detected.', $this->token));
    }

    /**
     * Move one token ahead.
     *
     * @return void
     */
    private function advanceToken()
    {
        if (!($this->tokenIs(T_WHITESPACE) || $this->tokenIs(T_DOC_COMMENT))) {
            $this->prevToken = $this->token;
        }
        $this->token = next($this->tokens);
    }

    /**
     * {@inheritDoc}
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * {@inheritDoc}
     */
    public function getNextToken($searchfor = false)
    {
        $this->advanceToken();
        if (false !== $searchfor) {
            $this->skipUntilSearchedToken($searchfor);
        } else {
            $this->skipWhiteSpaceAndComments();
        }
    }

    /**
     * Skip all tokens until the matched token has been encountered or no more tokens are available.
     *
     * @param mixed $searchFor The token to search for.
     *
     * @return void
     */
    private function skipUntilSearchedToken($searchFor)
    {
        while ($this->token) {
            if ((is_string($searchFor) && ($searchFor === $this->token))
                || (is_int($searchFor) && is_array($this->token) && ($searchFor === $this->token[0]))
            ) {
                break;
            }
            $this->advanceToken();
        }
    }

    /**
     * Skip until the next non whitespace and doc comment.
     *
     * @return void
     */
    private function skipWhiteSpaceAndComments()
    {
        while ($this->tokenIs(T_WHITESPACE) || $this->tokenIs(T_DOC_COMMENT)) {
            $this->advanceToken();
        }
    }
}
