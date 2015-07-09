<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author       Tristan Lins <tristan.lins@bit3.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Translation\Contao;

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
    protected $file;

    /**
     * The tokens contained in the parser.
     *
     * @var array
     */
    protected $tokens;

    /**
     * The previous token.
     *
     * @var string|int|null|array
     */
    protected $prevToken;

    /**
     * Index of the previous token.
     *
     * @var int
     */
    protected $token;

    /**
     * The stack of language keys.
     *
     * @var string[]
     */
    protected $keystack;

    /**
     * The auto index.
     *
     * @var array
     */
    protected $autoIndex = array();

    /**
     * Create a new instance.
     *
     * @param ContaoFile $file The file to parse.
     */
    public function __construct($file)
    {
        $this->file = $file;
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
        $this->file->debug($message);
    }

    /**
     * {@inheritDoc}
     */
    public function pushStack($value)
    {
        if (is_array($value)) {
            if ($value[0] == T_CONSTANT_ENCAPSED_STRING) {
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
        $this->file->setValue($key, $value);
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

                if ($this->tokenIs(T_ARRAY)) {
                    $arrayParser = new ArrayParser($this, 1);
                    $arrayParser->parse();
                    $this->debug('After array. ' . var_export($this->getToken(), true));
                } else {
                    $subparser = new StringValue($this);
                    $subparser->parse();

                    $this->file->setValue(implode('.', $this->keystack), $subparser->getValue());
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

        $subparser = new StringValue($this);
        $subparser->parse();

        if ($subparser->getValue() === null) {
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
            if (($this->token[0] == T_VARIABLE) && $this->token[1] == '$GLOBALS') {
                $this->getNextToken();
                if ($this->tokenIs('[')) {
                    $this->getNextToken();

                    $this->checkIsNotString(T_CONSTANT_ENCAPSED_STRING);
                    // Wrong sub array.
                    if (substr($this->token[1], 1, -1) != 'TL_LANG') {
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
        if (!is_string($this->token) || ($value && ($this->token != $value))) {
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
        if (is_string($this->token) || ($value && ($this->token[0] != $value))) {
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
            return ($this->token == $type);
        }

        return ($this->token[0] == $type);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Exception With the unexpected token in the message.
     */
    public function bailUnexpectedToken($expected = false)
    {
        if (is_array($this->token)) {
            if ($expected) {
                throw new \Exception(
                    sprintf(
                        'Unexpected token %s detected at position %d - value: %s, expected %s',
                        token_name($this->token[0]),
                        $this->token[2],
                        $this->token[1],
                        token_name($expected)
                    )
                );
            }

            throw new \Exception(
                sprintf(
                    'Unexpected token %s detected at position %d - value: %s',
                    token_name($this->token[0]),
                    $this->token[2],
                    $this->token[1]
                )
            );
        }

        throw new \Exception(sprintf('Unexpected token %s detected.', $this->token));
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
        if ($searchfor !== false) {
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
            if ((is_string($searchFor) && ($searchFor == $this->token))
                || (is_int($searchFor) && is_array($this->token) && ($searchFor == $this->token[0]))
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
