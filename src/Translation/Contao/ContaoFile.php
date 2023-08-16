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
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Traversable;

use function array_keys;
use function array_map;
use function dirname;
use function explode;
use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function implode;
use function is_dir;
use function mkdir;
use function preg_match;
use function rtrim;
use function sort;
use function sprintf;
use function str_repeat;
use function strlen;
use function strtr;
use function unlink;
use function var_export;

/**
 * This class implements a Contao language file handler.
 *
 * @extends AbstractFile<TranslationEntry, ContaoFile>
 */
final class ContaoFile extends AbstractFile
{
    /** The filename to work on. */
    private string $filename;

    /**
     * The language strings.
     *
     * @var array<string, string>
     */
    private array $langstrings = [];

    /**
     * The file header to use as phpdoc.
     *
     * @var list<string>
     */
    private array $fileHeader = [];

    /**
     * The language.
     */
    private string $language;

    /**
     * The timestamp when this file has been changed the last time.
     */
    private ?DateTimeInterface $lastChange = null;

    /**
     * Flag if the contents have been changed.
     */
    private bool $changed = false;

    /**
     * Create a new instance.
     *
     * @param string               $filename The filename.
     * @param LoggerInterface|null $logger The logger to use.
     */
    public function __construct($filename, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->filename = $filename;
        $this->language = basename(dirname($filename));

        if ($this->exists()) {
            $this->load();
        } else {
            $this->createBasicDocument();
        }
    }

    /**
     * Retrieve the filename.
     */
    public function getFileName(): string
    {
        return $this->filename;
    }

    public function keys(): array
    {
        return array_keys($this->langstrings);
    }

    public function remove(string $key): self
    {
        if (isset($this->langstrings[$key])) {
            $this->changed = true;
        }
        unset($this->langstrings[$key]);

        return $this;
    }

    public function set(string $key, string $value): self
    {
        if ($value !== $this->get($key)) {
            $this->langstrings[$key] = $value;
            $this->logger->debug('ContaoFile::setValue {key} => {value}', compact('key', 'value'));
            $this->changed = true;
        }

        return $this;
    }

    public function get(string $key): ?string
    {
        return $this->langstrings[$key] ?? null;
    }

    public function isChanged(): bool
    {
        return $this->changed;
    }

    public function exists(): bool
    {
        return file_exists($this->getFileName());
    }

    public function getLanguageCode(): string
    {
        return $this->language;
    }

    /**
     * Set the language.
     *
     * @param string $language The language.
     */
    public function setLanguage(string $language): void
    {
        if ($this->language === $language) {
            return;
        }
        $this->language = $language;
        $this->changed = true;
    }

    /**
     * Retrieve file header.
     *
     * @return list<string>
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
     * @param list<string> $fileHeader The new value.
     */
    public function setFileHeader(array $fileHeader): void
    {
        if ($this->fileHeader === $fileHeader) {
            return;
        }
        $this->fileHeader = $fileHeader;
        $this->changed = true;
    }

    public function getLastChange(): ?DateTimeInterface
    {
        return $this->lastChange;
    }

    /**
     * Set the last changed value.
     *
     * @param DateTimeInterface $lastChange The time this file has the last time been changed.
     */
    public function setLastChange(DateTimeInterface $lastChange): void
    {
        // Do not strict compare here as DateTimeInterface is comparable!
        if ($this->lastChange == $lastChange) {
            return;
        }

        $this->lastChange = $lastChange;
        $this->changed = true;
    }

    /**
     * Retrieve the file heading.
     */
    protected function getHead(): string
    {
        if (null === $lastChange = $this->lastChange) {
            $lastChange = new DateTimeImmutable();
        }
        $year = $lastChange->format('Y');
        $time = $lastChange->format('c');
        return "<?php\n\n/**\n" . implode("\n", array_map(function ($line) use ($time, $year) {
            return rtrim(' * ' . strtr($line, [
                    '$$lastchanged$$' => $time,
                    '$$language$$'    => $this->language,
                    '$$year$$'        => $year,
                ]));
        }, $this->fileHeader)) . "\n */\n";
    }

    /**
     * Create a basic document to work on.
     */
    protected function createBasicDocument(): void
    {
        $this->langstrings = [];
    }

    /**
     * Parse a language file into language strings.
     */
    protected function load(): void
    {
        $data = file_get_contents($this->filename);
        if (false !== ($time = filemtime($this->filename))) {
            $this->setLastChange(new DateTimeImmutable('@' . $time));
            // We do not want to track this as change on its own.
            $this->changed = false;
        }

        $this->langstrings = [];

        $parser = new Parser($this, $this->logger);
        $parser->setContent($data);
        $parser->parse();
    }

    public function save(): void
    {
        $keys = array_keys($this->langstrings);

        if (empty($keys)) {
            if (file_exists($this->filename)) {
                $this->logger->notice('File {file} is empty, deleting...', ['file' => $this->filename]);
                unlink($this->filename);
                $this->changed = false;
            }
            return;
        }

        sort($keys);
        $this->createPathIfNotExists();

        $maxlen       = 0;
        $langPrefixes = [];
        foreach ($keys as $key) {
            $tokens = explode('.', $key);
            if (preg_match('/tl_layout\.[a-z]+\.css\./', $key)) {
                $tokens = [$tokens[0], $tokens[1] . '.' . $tokens[2], $tokens[3]];
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

        $buffer = $this->getHead() . PHP_EOL;
        foreach ($keys as $key) {
            $prefix  = $langPrefixes[$key];
            $buffer .= sprintf(
                '%s = %s;' . PHP_EOL,
                $prefix . str_repeat(' ', ($maxlen - strlen($prefix))),
                var_export($this->get($key), true)
            );
        }
        $resource = fopen($this->filename, 'wb');
        fwrite($resource, $buffer);

        fclose($resource);
        $this->changed = false;
        $this->logger->notice('File {file} saved.', ['file' => $this->filename]);
    }

    public function getIterator(): Traversable
    {
        return new TranslationIterator($this);
    }

    /**
     * Ensure the configured path exists.
     *
     * @throws RuntimeException When the path could not be created.
     */
    private function createPathIfNotExists(): void
    {
        if (
            !is_dir($directory = dirname($this->filename))
            && !mkdir($directory, 0755, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }
    }
}
