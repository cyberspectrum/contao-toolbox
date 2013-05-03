<?php

namespace CyberSpectrum\Translation\Contao;


class ArrayParser implements ParserInterface
{
	/**
	 * @var Parser
	 */
	protected $parser;

	protected $value;

	protected $counter;

	protected $level;

	public function __construct(Parser $parser, $level = 0)
	{
		$this->parser  = $parser;
		$this->counter = 0;
		$this->level   = $level;
	}

	public function debug($message)
	{
		$this->parser->debug('ArrayParser ' . $this->level . ' ' . $message);
	}

	public function pushStack($value)
	{
		$this->parser->pushStack($value);
	}

	public function popStack()
	{
		return $this->parser->popStack();
	}

	public function resetStack()
	{
		$this->parser->resetStack();
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
		return $this->parser->tokenIs($type);
	}

	public function bailUnexpectedToken($expected = false)
	{
		$this->parser->bailUnexpectedToken($expected);
	}

	public function getToken()
	{
		return $this->parser->getToken();
	}

	public function getNextToken($searchfor = false)
	{
		$this->parser->getNextToken($searchfor);
	}

	public function parse()
	{
		$this->debug(' - enter.');

		$this->getNextToken();

		if (!$this->tokenIs('('))
		{
			$this->bailUnexpectedToken('(');
		}

		$this->getNextToken();

		while (true)
		{
			// Sub array without key.
			if ($this->tokenIs(T_ARRAY))
			{
				$this->debug('Sub array without key.');
				$this->pushStack($this->counter++);

				$subparser = new ArrayParser($this->parser, $this->level+1);
				$subparser->parse();

				$this->popStack();

				if ($this->tokenIs(','))
				{
					$this->getNextToken();
				}
				elseif ($this->tokenIs(')'))
				{
					$this->debug('closing bracket.');
					$this->getNextToken();
					break;
				}
			}
			else
			{
				$subparser = new StringValue($this->parser, $this->level+1);
				$subparser->parse();

				$key = $subparser->getValue();

				$this->debug('key: ' . var_export($key, true));

				if ($this->tokenIs(T_DOUBLE_ARROW))
				{
					// We MUST have an key when double arrow is encountered.
					if ($key === null)
					{
						$this->bailUnexpectedToken();
					}

					$this->pushStack($key);

					$this->getNextToken();

					// Sub array with key.
					if ($this->tokenIs(T_ARRAY))
					{
						$this->debug('Sub array with key.');
						$subparser = new ArrayParser($this->parser, $this->level+1);
						$subparser->parse();
					}
					// String item with key.
					else
					{
						$this->debug('String item with key.');
						$subparser = new StringValue($this->parser, $this->level+1);
						$subparser->parse();

						$this->parser->setValue($this->parser->getStack(), $subparser->getValue());
					}

					$this->popStack();
				}
				// String item without key.
				elseif ($this->tokenIs(',') || $this->tokenIs(')'))
				{
					$this->debug('String item without key.');
					$this->pushStack($this->counter++);
					$this->parser->setValue($this->parser->getStack(), $key);
					$this->popStack();
				}

				if ($this->tokenIs(','))
				{
					$this->getNextToken();
				}
				elseif ($this->tokenIs(')'))
				{
					$this->debug('closing bracket.');
					$this->getNextToken();
					break;
				}
			}
		}
		$this->debug(' - exit.');
	}
}