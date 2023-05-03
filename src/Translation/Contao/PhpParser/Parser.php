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

use Closure;
use CyberSpectrum\ContaoToolBox\Translation\Contao\ContaoFile;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * This class implements a generic parser.
 *
 * @psalm-import-type TToken from ParserInterface
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class Parser implements ParserInterface
{
    /** The file being parsed. */
    private ContaoFile $file;

    /** @var Closure(string, string): void */
    private Closure $fileAccessor;

    /** The logger to use. */
    private LoggerInterface $logger;

    /**
     * The tokens contained in the parser.
     *
     * Each individual token identifier is either
     *   - a single character (i.e.: ;, ., &gt;, !, etc...),
     *   - or a three element array containing the token index in element 0, the string content of the original
     *     token in element 1 and the line number in element 2.
     *
     * @list<TToken>
     */
    private array $tokens = [];

    /**
     * The current token.
     *
     * @var null|TToken
     */
    private null|string|array $token = null;

    /**
     * The stack of language keys.
     *
     * @var list<string>
     */
    private array $keyStack = [];

    /**
     * The auto index.
     *
     * @var array<string, int>
     */
    private array $autoIndex = [];

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
        // This is pretty hacky but works well enough.
        $fileAccessor = Closure::bind(
            function (string $key, string $value): void {
                /** @psalm-scope-this ContaoFile */
                $this->langstrings[$key] = $value;
            },
            $this->file,
            $this->file
        );
        assert($fileAccessor instanceof Closure);
        /** @psalm-var Closure(string, string): void $fileAccessor */

        $this->fileAccessor = $fileAccessor;
    }

    /**
     * Send a debug message to the attached file.
     *
     * @param string $message The message to send.
     */
    public function debug(string $message): void
    {
        $this->logger->debug($message);
    }

    public function pushStack(array|string $value): void
    {
        if (is_array($value)) {
            if (T_CONSTANT_ENCAPSED_STRING === $value[0]) {
                $this->keyStack[] = substr($value[1], 1, -1);
            } else {
                $this->keyStack[] = strval($value[1]);
            }
        } else {
            $this->keyStack[] = $value;
        }
        $this->debug('pushed stack ' . implode('.', $this->keyStack));
    }

    public function popStack(): string
    {
        $value = array_pop($this->keyStack);
        $this->debug('popped stack ' . implode('.', $this->keyStack));

        return $value;
    }

    public function resetStack(): void
    {
        $this->keyStack = array();
        $this->debug('stack reset');
    }

    /**
     * Fetch the stack either as array or as dot separated string.
     *
     * @param bool $imploded If true, the result will be a dot separated string, the stack array otherwise.
     *
     * @return string|list<string>
     *
     * @psalm-return ($imploded is true ? string : list<string>)
     */
    public function getStack(bool $imploded = true): array|string
    {
        return $imploded ? implode('.', $this->keyStack) : $this->keyStack;
    }

    /**
     * Set a value.
     *
     * @param string $key   The key to set in the file.
     * @param string $value The value to set.
     */
    public function setValue(string $key, string $value): void
    {
        $this->fileAccessor->__invoke($key, $value);
    }

    /**
     * Read a language string from the file.
     */
    private function readLangString(): void
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

                if ($this->tokenIsAnyOf(T_ARRAY, '[')) {
                    $arrayParser = new ArrayParser($this, 1);
                    $arrayParser->parse();
                } else {
                    $subparser = new StringValueParser($this);
                    $subparser->parse();

                    $this->setValue(implode('.', $this->keyStack), (string) $subparser->getValue());
                }

                continue;
            }
            if (!$this->tokenIs(']')) {
                $this->bailUnexpectedToken(']');
            }
        }

        if ($this->tokenIs(';')) {
            // Reset stack.
            $this->resetStack();
        }
    }

    /**
     * Spawn a sub parser to parse the [] expression.
     */
    private function subParserSquareBracket(): void
    {
        $this->getNextToken();

        $subparser = new StringValueParser($this);
        $subparser->parse();

        if (null === ($value = $subparser->getValue())) {
            // auto indexed array
            if (!$this->tokenIs(']')) {
                // invalid code?!
                $this->bailUnexpectedToken();
            }
            $path = implode('.', $this->keyStack);

            if (!isset($this->autoIndex[$path])) {
                $this->autoIndex[$path] = 0;
            } else {
                $this->autoIndex[$path]++;
            }
            $this->pushStack((string) $this->autoIndex[$path]);

            return;
        }
        $this->pushStack($value);
    }

    /**
     * Set the content.
     *
     * @param string $content The PHP text to parse.
     */
    public function setContent(string $content): void
    {
        $this->tokens = token_get_all($content);
        $this->token = null;
        $this->keyStack = [];
        $this->autoIndex = [];
    }

    public function parse(): void
    {
        $this->advanceToken();
        // Empty file.
        if (null === $this->token) {
            return;
        }

        $this->tryReadPhpDoc();

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
     * Ensure the current token is not a string and is of the given value.
     *
     * @param null|int $value The value.
     */
    private function checkIsNotString(?int $value): void
    {
        if (null === $this->token || is_string($this->token) || ($value && ($this->token[0] !== $value))) {
            $this->bailUnexpectedToken();
        }
    }

    /**
     * Check whether the current token matches the given value.
     *
     * @param string|int $type The type that is expected, either a string value or a tokenizer id.
     */
    public function tokenIs(string|int $type): bool
    {
        if (null === $this->token) {
            return false;
        }
        if (is_string($this->token)) {
            return ($this->token === $type);
        }

        return ($this->token[0] === $type);
    }

    public function tokenIsAnyOf(string|int ...$types): bool
    {
        foreach ($types as $type) {
            if ($this->tokenIs($type)) {
                return true;
            }
        }
        return false;
    }

    public function bailUnexpectedToken(false|int|string $expected = false): never
    {
        if (is_array($this->token)) {
            if (false !== $expected) {
                throw new RuntimeException(
                    sprintf(
                        'Unexpected token %s detected at position %d - value: %s, expected %s',
                        token_name($this->token[0]),
                        $this->token[2],
                        $this->token[1],
                        (is_string($expected) ? $expected : token_name($expected))
                    )
                );
            }

            throw new RuntimeException(
                sprintf(
                    'Unexpected token %s detected at position %d - value: %s',
                    token_name($this->token[0]),
                    $this->token[2],
                    $this->token[1]
                )
            );
        }

        throw new RuntimeException(sprintf('Unexpected token %s detected.', var_export($this->token, true)));
    }

    /**
     * Move one token ahead.
     */
    private function advanceToken(): void
    {
        /** @var TToken|false $token */
        $token = (null !== $this->token) ? next($this->tokens) : reset($this->tokens);
        if (false === $token) {
            $token = null;
        }
        $this->token = $token;
    }

    public function getToken(): null|string|array
    {
        return $this->token;
    }

    public function getNextToken(false|int|string $searchFor = false): void
    {
        $this->advanceToken();
        if (false !== $searchFor) {
            $this->skipUntilSearchedToken($searchFor);
        } else {
            $this->skipWhiteSpaceAndComments();
        }
    }

    /**
     * Skip all tokens until the matched token has been encountered or no more tokens are available.
     *
     * @param int|string $searchFor The token to search for.
     */
    private function skipUntilSearchedToken(int|string $searchFor): void
    {
        while ($this->token) {
            if (
                (is_string($searchFor) && ($searchFor === $this->token))
                || (is_int($searchFor) && is_array($this->token) && ($searchFor === $this->token[0]))
            ) {
                break;
            }
            $this->advanceToken();
        }
    }

    /**
     * Skip until the next non whitespace and doc comment.
     */
    private function skipWhiteSpaceAndComments(): void
    {
        while ($this->tokenIsAnyOf(T_WHITESPACE, T_DOC_COMMENT)) {
            $this->advanceToken();
        }
    }

    private function tryReadPhpDoc(): void
    {
        while ($this->tokenIsAnyOf(T_WHITESPACE)) {
            $this->advanceToken();
        }
        if (!$this->tokenIs(T_OPEN_TAG)) {
            $this->bailUnexpectedToken(T_OPEN_TAG);
        }
        $this->advanceToken();
        while ($this->tokenIsAnyOf(T_WHITESPACE)) {
            $this->advanceToken();
        }
        // Try to load the php Doc, if any.
        if ($this->tokenIs(T_DOC_COMMENT)) {
            assert(is_array($this->token));
            $phpDoc = [];
            foreach (explode("\n", $this->token[1]) as $line) {
                $line = trim($line);
                if (in_array($line, ['/**', '*/'], true)) {
                    continue;
                }
                $phpDoc[] = ltrim($line, ' *');
            }
            unset($line);

            $this->file->setFileHeader($phpDoc);
        }
    }
}
