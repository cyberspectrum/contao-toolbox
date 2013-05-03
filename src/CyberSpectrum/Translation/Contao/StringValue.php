<?php

namespace CyberSpectrum\Translation\Contao;

class StringValue implements ParserInterface
{
	protected $parser;

	protected $level;

	protected $data;

	public function __construct(Parser $parser, $level = 0)
	{
		$this->parser = $parser;
		$this->level  = $level;
	}

	function debug($message)
	{
		$this->parser->debug('StringValue ' .$this->level . ' ' . $message);
	}

	function pushStack($value)
	{
		$this->parser->pushStack($value);
	}

	function popStack()
	{
		return $this->parser->popStack();
	}

	function resetStack()
	{
		$this->parser->resetStack();
	}

	/**
	 * Check whether the current token matches the given value.
	 *
	 * @param mixed $type The type that is expected, either a string value or a tokenizer id.
	 *
	 * @return bool
	 */function tokenIs($type)
	{
		return $this->parser->tokenIs($type);
	}

	function bailUnexpectedToken($expected = false)
	{
		$this->parser->bailUnexpectedToken($expected);
	}

	function getToken()
	{
		return $this->parser->getToken();
	}

	function getNextToken($searchfor = false)
	{
		$this->parser->getNextToken($searchfor);
	}

	public function parse()
	{
		$this->debug(' - enter.');

		while (true)
		{
			if ($this->tokenIs(T_CONSTANT_ENCAPSED_STRING))
			{
				$token = $this->getToken();
				$this->data[] = substr($token[1], 1, -1);
			}
			elseif ($this->tokenIs(T_LNUMBER))
			{
				$token = $this->getToken();
				$this->data[] = strval($token[1]);
			}
			elseif (
				$this->tokenIs(';')
				|| $this->tokenIs(',')
				|| $this->tokenIs(')')
				|| $this->tokenIs(']')
				|| $this->tokenIs(T_DOUBLE_ARROW)
			)
			{
				break;
			}
			else
			{
				$this->bailUnexpectedToken();
			}
			$this->getNextToken();
		}
		$this->debug(' - exit.');
	}

	public function getValue()
	{
		if (!(is_array($this->data) && count($this->data)))
		{
			return null;
		}
		return implode('', $this->data);
	}
}