<?php

namespace CyberSpectrum\Translation\Contao;

class File implements \IteratorAggregate
{
	/**
	 * The filename to work on.
	 *
	 * @var string
	 */
	protected $filename;

	protected $head;

	protected $langstrings;

	protected $language;

	protected $transifexProject;

	protected $lastchange;

	/**
	 * Stack of keys - needed in parsing of php language arrays.
	 *
	 * @var array
	 */
	protected $keystack = array();

	public function __construct($filename, $transifexProject = 'contao')
	{
		$this->filename = $filename;
		$this->language = basename(dirname($filename));

		if (file_exists($filename))
		{
			$this->load();
		} else {
			$this->createBasicDocument();
		}
	}

	public function debug($message)
	{
//		echo $message . "\n";
	}

	/**
	 * Set the project name that this language file belongs to at transifex.
	 *
	 * @param string $name The project name at transifex, will get used to generate the doc comment url.
	 *
	 * @return void
	 */
	public function setTransifexProject($name)
	{
		$this->transifexProject = $name;
	}

	public function setLanguage($language)
	{
		$this->language = $language;
	}

	public function setLastChange($timestamp)
	{
		$this->lastchange = $timestamp;
	}

	/**
	 * Return the default heading,
	 *
	 * @return string
	 */
	protected function getDefaultHead()
	{
		return '<?php
/**
 * Translations are managed using Transifex. To create a new translation
 * or to help to maintain an existing one, please register at transifex.com.
 *
 * @link http://help.transifex.com/intro/translating.html
 * @link https://www.transifex.com/projects/p/$$project$$/language/$$lang$$/
 *
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 *
 * last-updated: $$lastchanged$$
 */
';
	}

	protected function getHead()
	{
		$data = $this->head;

		if (preg_match('#last-updated: (.*)#', $data, $match))
		{
			$data = str_replace($match[1], date('c', $this->lastchange), $data);
		}

		if ($this->transifexProject && preg_match_all('#https://www.transifex.com/projects/p/.*/language/.*/#', $data, $match, PREG_OFFSET_CAPTURE))
		{
			$data = substr_replace(
				$data,
				sprintf(
					'https://www.transifex.com/projects/p/%s/language/%s/',
					$this->transifexProject,
					$this->language
				),
				$match[0][0][1],
				strlen($match[0][0][0])
			);
		}

		return $data;
	}

	/**
	 * Create a basic document to work on.
	 */
	protected function createBasicDocument()
	{
		$this->head    = $this->getDefaultHead();
		$this->langstrings = array();
	}

	public function setValue($key, $value)
	{
		$this->langstrings[$key] = $value;
		$this->debug('SetValue ' . $key . ' => ' . $value);
	}

	public function getValue($key)
	{
		return isset($this->langstrings[$key]) ? $this->langstrings[$key] : null;
	}

	public function removeValue($key)
	{
		unset($this->langstrings[$key]);
	}

	public function getKeys()
	{
		return array_keys($this->langstrings);
	}

	/**
	 * Parse a language file into language strings.
	 *
	 * @return void
	 */
	protected function load()
	{
		$data = file_get_contents($this->filename);
		// Ok, here comes the dirty work.
		// We take everything at the beginning of the file until the closing of the first doc comment.
		preg_match('#^(.+\*/)#sU', $data, $matches);

		if ($matches && count($matches[0]))
		{
			$this->head = $matches[0];
			if (preg_match('#https://www.transifex.com/projects/p/(.*)/language/(.*)/#', $this->head, $match, PREG_OFFSET_CAPTURE))
			{
				$this->transifexProject = $match[1][0][0];
				$this->language         = $match[2][0][0];
			}
		} else {
			$this->head = $this->getDefaultHead();
		}
		$this->langstrings = array();

		$parser = new Parser($this);
		$parser->setContent($data);
		$parser->parse();
	}


	public function save()
	{
		$keys   = array_keys($this->langstrings);
		sort($keys);

		$maxlen = 0;
		$langPrefixes = array();
		foreach ($keys as $key)
		{
			$tokens     = explode('.', $key);
			$langPrefix = '$GLOBALS[\'TL_LANG\']';
			foreach ($tokens as $token)
			{
				$langPrefix .= '[\'' . $token . '\']';
			}

			$langPrefixes[$key] = $langPrefix;

			if (strlen($langPrefix) > $maxlen)
			{
				$maxlen = strlen($langPrefix);
			}
		}

		$buffer = $this->getHead() . PHP_EOL . PHP_EOL;
		foreach($keys as $key)
		{
			$prefix  = $langPrefixes[$key];
			$buffer .= sprintf('%s = %s;' . PHP_EOL,
				$prefix . str_repeat(' ', ($maxlen - strlen($prefix))),
				var_export($this->getValue($key), true)
			);
		}
		$buffer .= PHP_EOL;
		$res = fopen($this->filename, 'wb');
		fputs($res, $buffer);

		fclose($res);
	}

	public function getIterator()
	{
		return new TranslationIterator($this);
	}
}
