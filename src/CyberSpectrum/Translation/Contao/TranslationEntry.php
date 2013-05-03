<?php

namespace CyberSpectrum\Translation\Contao;

class TranslationEntry
{
	/**
	 * The document we are belonging to.
	 *
	 * @var File
	 */
	protected $doc;

	/**
	 * The translation key for this entry.
	 *
	 * @var string
	 */
	protected $key;

	public function __construct($key, $doc)
	{
		if (!strlen($key))
		{
			throw new \Exception('Empty Id passed.');
		}

		$this->key = $key;
		$this->doc = $doc;
	}

	/**
	 * @param string $value the value to set.
	 *
	 * @return TranslationEntry
	 */
	public function setValue($value)
	{
		$this->doc->setValue($this->getKey(), $value);

		return $this;
	}

	/**
	 * Fetches the value from this entry.
	 *
	 * @return null|string
	 */
	public function getValue()
	{
		return $this->doc->getValue($this->getKey());
	}

	public function getKey()
	{
		return $this->key;
	}
}