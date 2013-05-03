<?php

namespace CyberSpectrum\Translation\Contao;


class TranslationIterator implements \Iterator
{
	/**
	 * @var File
	 */
	protected $file;

	protected $keys;

	protected $position = 0;

	/**
	 * @param File $file the Contao file we are working on.
	 */
	public function __construct($file)
	{
		$this->position = 0;
		$this->file     = $file;
		$this->keys     = $file->getKeys();
	}

	function rewind()
	{
		$this->position = 0;
	}

	function current()
	{
		return new TranslationEntry($this->key(), $this->file);
	}

	function key()
	{
		return $this->keys[$this->position];
	}

	function next()
	{
		++$this->position;
	}

	function valid()
	{
		return isset($this->keys[$this->position]);
	}
}