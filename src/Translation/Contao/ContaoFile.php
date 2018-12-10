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
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\ContaoToolBox\Translation\Contao;

use CyberSpectrum\ContaoToolBox\Translation\Base\AbstractFile;
use CyberSpectrum\ContaoToolBox\Translation\Contao\PhpParser\Parser;
use Psr\Log\LoggerInterface;

/**
 * This class implements a Contao language file handler.
 */
class ContaoFile extends AbstractFile
{
    /**
     * The filename to work on.
     *
     * @var string
     */
    private $filename;

    /**
     * The language strings.
     *
     * @var array
     */
    private $langstrings;

    /**
     * The file header to use as phpdoc.
     *
     * @var string[]
     */
    private $fileHeader;

    /**
     * The language.
     *
     * @var string
     */
    private $language;

    /**
     * The timestamp when this file has been changed the last time.
     *
     * @var int
     */
    private $lastchange;

    /**
     * Stack of keys - needed in parsing of php language arrays.
     *
     * @var array
     */
    protected $keystack = array();

    /**
     * Flag if the contents have been changed.
     *
     * @var bool
     */
    private $changed = false;

    /**
     * Create a new instance.
     *
     * @param string          $filename The filename.
     *
     * @param LoggerInterface $logger   The logger to use.
     */
    public function __construct($filename, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->filename = $filename;
        $this->language = basename(dirname($filename));

        if (file_exists($filename)) {
            $this->load();
        } else {
            $this->createBasicDocument();
        }
    }

    /**
     * Retrieve the filename.
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->filename;
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return array_keys($this->langstrings);
    }

    /**
     * {@inheritDoc}
     *
     * @return ContaoFile
     */
    public function remove($key)
    {
        if (isset($this->langstrings[$key])) {
            $this->changed = true;
        }
        unset($this->langstrings[$key]);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return ContaoFile
     */
    public function set($key, $value)
    {
        if ($value !== $this->get($key)) {
            $this->langstrings[$key] = $value;
            $this->logger->debug('ContaoFile::setValue {key} => {value}', compact('key', 'value'));
            $this->changed = true;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key)
    {
        return isset($this->langstrings[$key]) ? $this->langstrings[$key] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function isChanged()
    {
        return $this->changed;
    }

    /**
     * {@inheritDoc}
     */
    public function getLanguageCode()
    {
        return $this->language;
    }

    /**
     * Retrieve file header.
     *
     * @return string[]
     */
    public function getFileHeader(): array
    {
        return $this->fileHeader ?:
            [
                'Translations are managed using Transifex. To create a new translation',
                'or to help to maintain an existing one, please register at transifex.com.',
                '',
                '@link https://www.transifex.com/signup/',
                '',
                'last-updated: $$lastchanged$$',
            ];
    }

    /**
     * Set file header.
     *
     * @param string[] $fileHeader The new value.
     *
     * @return ContaoFile
     */
    public function setFileHeader(array $fileHeader): ContaoFile
    {
        $this->fileHeader = $fileHeader;

        return $this;
    }

    /**
     * Set the language.
     *
     * @param string $language The language.
     *
     * @return void
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Set the last changed value.
     *
     * @param int $timestamp The timestamp this file has the last time been changed.
     *
     * @return void
     */
    public function setLastChange($timestamp)
    {
        $this->lastchange = $timestamp;

        return $this;
    }

    /**
     * Retrieve the file heading.
     *
     * @return mixed
     */
    protected function getHead()
    {
        if (null === $time = $this->lastchange) {
            $time = time();
        }
        $year = date('Y', $time);
        $time = date('c', $time);
        $data = "<?php\n/**\n" . implode("\n", \array_map(function ($line) use ($time, $year) {
            return rtrim(' * ' . strtr($line, [
                    '$$lastchanged$$' => $time,
                    '$$language$$'    => $this->language,
                    '$$year$$'        => $year,
                ]));
        }, $this->fileHeader)) . "\n */\n";

        return $data;
    }

    /**
     * Create a basic document to work on.
     *
     * @return void
     */
    protected function createBasicDocument()
    {
        $this->langstrings = array();
    }

    /**
     * Parse a language file into language strings.
     *
     * @return void
     */
    protected function load()
    {
        $data = file_get_contents($this->filename);

        $this->langstrings = array();

        $parser = new Parser($this, $this->logger);
        $parser->setContent($data);
        $parser->parse();
    }

    /**
     * Save the file to disk.
     *
     * @return void
     */
    public function save()
    {
        $keys = array_keys($this->langstrings);

        if (empty($keys) && file_exists($this->filename)) {
            $this->logger->notice('File {file} is empty, deleting...', ['file' => $this->filename]);
            unlink($this->filename);
            $this->changed = false;
            return;
        }

        sort($keys);
        $this->createPathIfNotExists();

        $maxlen       = 0;
        $langPrefixes = array();
        foreach ($keys as $key) {
            $tokens = explode('.', $key);
            if (preg_match('/tl_layout\.[a-z]+\.css\./', $key)) {
                $tokens = array($tokens[0], $tokens[1] . '.' . $tokens[2], $tokens[3]);
            }
            $langPrefix = '$GLOBALS[\'TL_LANG\']';
            foreach ($tokens as $token) {
                $langPrefix .= '[\'' . $token . '\']';
            }

            $langPrefixes[$key] = $langPrefix;

            if (strlen($langPrefix) > $maxlen) {
                $maxlen = strlen($langPrefix);
            }
        }

        $buffer = $this->getHead() . PHP_EOL . PHP_EOL;
        foreach ($keys as $key) {
            $prefix  = $langPrefixes[$key];
            $buffer .= sprintf(
                '%s = %s;' . PHP_EOL,
                $prefix . str_repeat(' ', ($maxlen - strlen($prefix))),
                var_export($this->get($key), true)
            );
        }
        $buffer  .= PHP_EOL;
        $resource = fopen($this->filename, 'wb');
        fwrite($resource, $buffer);

        fclose($resource);
        $this->changed = false;
        $this->logger->notice('File {file} saved.', ['file' => $this->filename]);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new TranslationIterator($this);
    }

    /**
     * Ensure the configured path exists.
     *
     * @return void
     *
     * @throws \RuntimeException When the path could not be created.
     */
    private function createPathIfNotExists()
    {
        if (!is_dir($directory = dirname($this->filename))
            && !mkdir($directory, 0755, true)
            && !is_dir($directory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
    }
}
