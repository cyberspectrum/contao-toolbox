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
     * The file header.
     *
     * @var string
     */
    private $head;

    /**
     * The language strings.
     *
     * @var array
     */
    private $langstrings;

    /**
     * The language.
     *
     * @var string
     */
    private $language;

    /**
     * The name of the project on transifex.
     *
     * @var string
     */
    private $transifexProject;

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
            $this->logger->debug('ContaoFile::setValue {key} => {value}', ['key' => $key, 'value' => $value]);
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
    }

    /**
     * Return the default heading.
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
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 *
 * @link https://www.transifex.com/signup/
 * @link https://www.transifex.com/projects/p/$$project$$/language/$$lang$$/
 *
 * last-updated: $$lastchanged$$
 */
';
    }

    /**
     * Retrieve the file heading.
     *
     * @return mixed
     */
    protected function getHead()
    {
        $data = $this->head;

        if (preg_match('#last-updated: (.*)#', $data, $match)) {
            $data = str_replace($match[1], date('c', $this->lastchange), $data);
        }

        if ($this->transifexProject
            && preg_match_all(
                '#https://www.transifex.com/projects/p/.*/language/.*/#',
                $data,
                $match,
                PREG_OFFSET_CAPTURE
            )
        ) {
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
     *
     * @return void
     */
    protected function createBasicDocument()
    {
        $this->head        = $this->getDefaultHead();
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
        // Ok, here comes the dirty work.
        // We take everything at the beginning of the file until the closing of the first doc comment.
        preg_match('#^(.+\*/)#sU', $data, $matches);

        if ($matches && count($matches[0])) {
            $this->head = $matches[0];
            if (preg_match(
                '#https://www.transifex.com/projects/p/(.*)/language/(.*)/#',
                $this->head,
                $match,
                PREG_OFFSET_CAPTURE
            )) {
                $this->transifexProject = $match[1][0];
                $this->language         = $match[2][0];
            }
        } else {
            $this->head = $this->getDefaultHead();
        }
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
        if (!is_dir($directory = dirname($this->filename))) {
            mkdir($directory, 0755, true);
        }

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
        fputs($resource, $buffer);

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
}
