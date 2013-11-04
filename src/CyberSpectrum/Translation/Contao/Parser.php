<?php

namespace CyberSpectrum\Translation\Contao;

class Parser implements ParserInterface
{
	/**
	 * @var File
	 */
	protected $file;

	protected $tokens;

	protected $prevToken;

	protected $token;

	protected $keystack;

	protected $autoIndex = array();

	public function __construct($file)
	{
		$this->file = $file;
	}

	public function debug($message)
	{
		$this->file->debug($message);
	}

	public function pushStack($value)
	{
		if (is_array($value))
		{
			if ($value[0] == T_CONSTANT_ENCAPSED_STRING)
			{
				$this->keystack[] = substr($value[1], 1, -1);
			}
			else {
				$this->keystack[] = strval($value[1]);
			}

		} else {
			$this->keystack[] = $value;
		}
		$this->debug('pushed stack '.implode('.', $this->keystack));
	}

	public function popStack()
	{
		$value = array_pop($this->keystack);
		$this->debug('popped stack '.implode('.', $this->keystack));
		return $value;
	}

	public function resetStack()
	{
		$this->keystack = array();
		$this->debug('stack reset');
	}

	public function getStack($imploded = true)
	{
		return $imploded ? implode('.', $this->keystack) : $this->keystack;
	}

	public function setValue($key, $value)
	{
		$this->file->setValue($key, $value);
	}

	protected function readLangString()
	{
		while (!$this->tokenIs(';'))
		{
			$this->getNextToken();

			if ($this->tokenIs('['))
			{
				$this->getNextToken();

				$subparser = new StringValue($this);
				$subparser->parse();

				if ($subparser->getValue() === null)
				{
					// auto indexed array
					if ($this->tokenIs(']')) {
						$path = implode('.', $this->keystack);

						if (!isset($this->autoIndex[$path])) {
							$this->autoIndex[$path] = 0;
						}
						else {
							$this->autoIndex[$path] ++;
						}

						$this->pushStack($this->autoIndex[$path]);
					}

					// invalid code?!
					else {
						$this->bailUnexpectedToken();
					}
				}
				else
				{
					$this->pushStack($subparser->getValue());
				}
			}
			elseif ($this->tokenIs('='))
			{
				// right hand of the assignment.
				$this->getNextToken();

				if($this->tokenIs(T_ARRAY))
				{
					$arrayParser = new ArrayParser($this, 1);
					$arrayParser->parse();
					$this->debug('After array. ' . var_export($this->getToken(), true));
					// $this->getNextToken();
				}
				else
				{
					$subparser = new StringValue($this);
					$subparser->parse();

					$this->file->setValue(implode('.', $this->keystack), $subparser->getValue());
				}
			}
			elseif (!$this->tokenIs(']'))
			{
				$this->bailUnexpectedToken();
			}
		}

		if ($this->tokenIs(';'))
		{
			// Reset stack.
			$this->resetStack();
		}
	}

	public function setContent($content)
	{
		$this->tokens = token_get_all($content);
	}

	public function parse()
	{
		$this->getNextToken(T_VARIABLE);
		while ($this->token)
		{
			if (($this->token[0] == T_VARIABLE) && $this->token[1] == '$GLOBALS')
			{
				$this->getNextToken();
				if ($this->tokenIs('['))
				{
					$this->getNextToken();

					$this->checkIsNotString(T_CONSTANT_ENCAPSED_STRING);
					// Wrong sub array.
					if (substr($this->token[1], 1, -1) != 'TL_LANG')
					{
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
	 * @param mixed $value The Value that the current token shall have.
	 *
	 * @param bool $expected
	 */
	public function checkIsString($value=false, $expected = false)
	{
		if (!is_string($this->token) || ($value && ($this->token != $value)))
		{
			$this->bailUnexpectedToken($this->token, $expected);
		}
	}

	/**
	 * Ensure the current token is not a string and optionally is of the given value.
	 *
	 * @param mixed $value Optional the value
	 *
	 * @param bool $expected
	 */
	public function checkIsNotString($value=false, $expected = false)
	{
		if (is_string($this->token) || ($value && ($this->token[0] != $value)))
		{
			$this->bailUnexpectedToken($this->token, $expected);
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
		if (is_string($this->token))
		{
			return ($this->token == $type);
		}
		return ($this->token[0] == $type);
	}

	/**
	 * Check whether the current token matches the given value.
	 *
	 * @param mixed $type The type that is expected, either a string value or a tokenizer id.
	 *
	 * @return bool
	 */
	public function prevTokenIs($type)
	{
		if (is_string($this->prevToken))
		{
			return ($this->prevToken == $type);
		}
		return ($this->prevToken[0] == $type);
	}

	public function bailUnexpectedToken($expected = false)
	{
		if (is_array($this->token))
		{
			if ($expected)
			{
				throw new \Exception(sprintf(
					'Unexpected token %s detected at position %d - value: %s, expected %s',
					token_name($this->token[0]),
					$this->token[2],
					$this->token[1],
					token_name($expected)
				));
			}

			throw new \Exception(sprintf(
				'Unexpected token %s detected at position %d - value: %s',
				token_name($this->token[0]),
				$this->token[2],
				$this->token[1]
			));
		}

		throw new \Exception(sprintf('Unexpected token %s detected.', $this->token));
	}

	protected function advanceToken()
	{
		if (!($this->tokenIs(T_WHITESPACE) || $this->tokenIs(T_DOC_COMMENT)))
		{
			$this->prevToken = $this->token;
		}
		$this->token = next($this->tokens);
	}

	public function getToken()
	{
		return $this->token;
	}

	public function getNextToken($searchfor = false)
	{
		$this->advanceToken();
		if ($searchfor !== false)
		{
			while ($this->token)
			{
				if ((is_string($searchfor) && ($searchfor == $this->token))
				|| (is_int($searchfor) && is_array($this->token) && ($searchfor == $this->token[0])))
				{
					break;
				}
				$this->advanceToken();
			}
		} else {
			while ($this->tokenIs(T_WHITESPACE) || $this->tokenIs(T_DOC_COMMENT))
			{
				$this->advanceToken();
			}
		}
	}
}