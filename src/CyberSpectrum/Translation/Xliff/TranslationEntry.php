<?php

namespace CyberSpectrum\Translation\Xliff;

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
	public function setSource($value)
	{
		$this->doc->setSource($this->getKey(), $value);

		return $this;
	}

	/**
	 * Fetches the source value from this entry.
	 *
	 * @return null|string
	 */
	public function getSource()
	{
		return $this->doc->getSource($this->getKey());
	}

	/**
	 * @param string $value the value to set.
	 *
	 * @return TranslationEntry
	 */
	public function setTarget($value)
	{
		$this->doc->setTarget($this->getKey(), $value);

		return $this;
	}

	/**
	 * Fetches the target value from this entry.
	 *
	 * @return null|string
	 */
	public function getTarget()
	{
		return $this->doc->getTarget($this->getKey());
	}

	public function getKey()
	{
		return $this->key;
	}
}