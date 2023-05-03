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

namespace CyberSpectrum\ContaoToolBox\Translation\Xliff;

use CyberSpectrum\ContaoToolBox\Translation\Base\AbstractFile;
use DateTimeImmutable;
use DateTimeInterface;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Traversable;

use function dirname;
use function file_exists;
use function is_dir;
use function mkdir;
use function sprintf;
use function unlink;
use function var_export;

/**
 * This class represents a XLIFF translation file.
 *
 * @extends AbstractFile<TranslationEntry, XliffFile>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class XliffFile extends AbstractFile
{
    /**
     * The xliff Namespace.
     */
    public const NS = 'urn:oasis:names:tc:xliff:document:1.2';

    /**
     * The document we are currently working on.
     */
    protected DOMDocument $doc;

    /**
     * The filename to work on.
     */
    protected ?string $filename;

    /**
     * The mode we are working in, either "source" or "target".
     */
    private string $mode;

    /**
     * Flag if the contents have been changed.
     */
    private bool $changed = false;

    /**
     * Create a new instance.
     *
     * @param string|null          $filename The filename to use or null when none should be loaded.
     * @param LoggerInterface|null $logger   The logger to use.
     */
    public function __construct(?string $filename = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->filename = $filename;

        $this->doc                     = new DOMDocument('1.0', 'UTF-8');
        $this->doc->preserveWhiteSpace = false;
        $this->doc->formatOutput       = true;

        $this->load($filename);
        $this->mode = 'target';
    }

    /**
     * Switch to the passed manipulation mode.
     *
     * @param string $mode The mode to use (either 'source' or 'target').
     *
     * @throws InvalidArgumentException When an invalid mode has been passed.
     */
    public function setMode(string $mode): self
    {
        if ('source' !== $mode && 'target' !== $mode) {
            throw new InvalidArgumentException('Invalid mode provided ' . $mode);
        }

        if ($mode !== $this->mode) {
            $this->mode = $mode;
            $this->logger->debug('Switched XLIFF context to {mode}', ['mode' => $mode]);
        }

        return $this;
    }

    /**
     * Retrieve the current file mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException When an empty id is encountered.
     */
    public function keys(): array
    {
        /** @var DOMNodeList $tmp */
        $transUnits = $this->getXPath()->query('/xliff:xliff/xliff:file/xliff:body/xliff:trans-unit');

        $result = [];

        if ($transUnits->length > 0) {
            /** @var DOMElement $element */
            foreach ($transUnits as $element) {
                if (!$this->getAttribute($element, 'id')) {
                    throw new RuntimeException('Empty Id: ' . var_export($element, true));
                }
                $result[] = $this->getAttribute($element, 'id');
            }
        }

        return $result;
    }

    public function remove(string $key): self
    {
        $unit = $this->searchForId($key);
        if ($unit) {
            $unit->parentNode?->removeChild($unit);
            $this->changed = true;
        }

        return $this;
    }

    public function set(string $key, string $value): self
    {
        $unit   = $this->searchOrCreateId($key);
        $source = $this->getXPathFirstItem('xliff:' . $this->mode, $unit);

        // If already present check
        if (null === $source) {
            $source = $unit->appendChild($this->createElement($this->mode));
            // Mark changed, we add the key here.
            $this->changed = true;
        } elseif ($source->firstChild) {
            if ($value === $source->firstChild->textContent) {
                // Nothing changed, we can exit here.
                return $this;
            }
            $this->changed = true;
            // If already present, we have to remove the textnode if one exists as otherwise the value will get
            // appended.
            $source->removeChild($source->firstChild);
        }

        $source->appendChild($this->doc->createTextNode($value));

        return $this;
    }

    public function get(string $key): ?string
    {
        $unit = $this->searchForId($key);

        $target = $this->getXPathFirstItem('xliff:' . $this->mode, $unit);
        if ($target && $target->firstChild) {
            return $target->firstChild->nodeValue;
        }

        return null;
    }

    public function isChanged(): bool
    {
        return $this->changed;
    }

    public function getLanguageCode(): string
    {
        return $this->getTgtLang();
    }

    /**
     * Save the contents to disk.
     *
     * @throws RuntimeException When the directory can not be created.
     */
    public function save(): void
    {
        if ($this->filename) {
            if (empty($this->keys()) && file_exists($this->filename)) {
                $this->logger->notice('File {file} is empty, deleting...', ['file' => $this->filename]);
                unlink($this->filename);
                $this->changed = false;
                return;
            }

            if (
                !is_dir($directory = dirname($this->filename))
                && !mkdir($directory, 0755, true)
                && !is_dir($directory)
            ) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }

            $this->doc->save($this->filename);
            $this->changed = false;
            $this->logger->notice('File {file} saved.', ['file' => $this->filename]);
        }
    }

    /**
     * Load the content from a string.
     */
    public function saveXML(): string
    {
        return $this->doc->saveXML();
    }

    /**
     * Load the file from disk.
     *
     * @param string|null $filename The filename.
     */
    public function load(?string $filename): void
    {
        if ($filename && file_exists($filename)) {
            $this->doc->load($filename, LIBXML_NSCLEAN);
        } else {
            $this->createBasicDocument();
        }
    }

    /**
     * Load the content from a string.
     *
     * @param string $content The XML string.
     */
    public function loadXML(string $content): void
    {
        $this->doc->loadXML($content, LIBXML_NSCLEAN);
    }

    /**
     * Create the basic document structure.
     */
    protected function createBasicDocument(): void
    {
        $this->doc->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2"><file><body></body></file></xliff>',
            LIBXML_NSCLEAN
        );

        // Set some basic information.
        $this->setDataType('plaintext');
        $this->setDate(new DateTimeImmutable());
        $this->setOriginal('');
        $this->setSrcLang('en');
        $this->setTgtLang('en');
    }

    /**
     * Retrieve the filename.
     */
    public function getFileName(): ?string
    {
        return $this->filename;
    }

    /**
     * Set the datatype in this file.
     *
     * See http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#datatype
     *
     * You may use a custom datatype here but have to prefix it with "x-".
     *
     * @param string $datatype The data type.
     */
    public function setDataType(string $datatype): void
    {
        $this->setFileAttribute('datatype', $datatype);
    }

    /**
     * Get the datat ype for this file.
     *
     * See http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#datatype
     */
    public function getDataType(): string
    {
        return $this->getFileAttribute('datatype');
    }

    /**
     * Sets the last modification time in this file.
     */
    public function setDate(DateTimeInterface $date): void
    {
        $this->setFileAttribute('date', $date->format(DateTimeInterface::ATOM));
    }

    /**
     * Return the last modification time from this file as timestamp.
     */
    public function getDate(): DateTimeInterface
    {
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $this->getFileAttribute('date'));
        if (false === $date) {
            throw new InvalidArgumentException('Invalid date format: ' . $this->getFileAttribute('date'));
        }

        return $date;
    }

    /**
     * Set the "original" data source id value in the file.
     *
     * You will most likely the file name of the original resource or something like this here.
     *
     * @param string $original The name of the original data source.
     */
    public function setOriginal(string $original): void
    {
        $this->setFileAttribute('original', $original);
    }

    /**
     * Get the original resource name from this file.
     */
    public function getOriginal(): string
    {
        return $this->getFileAttribute('original');
    }

    /**
     * Set the source language for this file.
     *
     * @param string $language The language code from ISO 639-1.
     */
    public function setSrcLang(string $language): void
    {
        $this->setFileAttribute('source-language', $language);
    }

    /**
     * Get the current source language for this file.
     *
     * @return string The language code from ISO 639-1
     */
    public function getSrcLang(): string
    {
        return $this->getFileAttribute('source-language');
    }

    /**
     * Set the target language for this file.
     *
     * @param string $language The language code from ISO 639-1.
     */
    public function setTgtLang(string $language): void
    {
        $this->setFileAttribute('target-language', $language);
    }

    /**
     * Get the current target language for this file.
     *
     * @return string The language code from ISO 639-1.
     */
    public function getTgtLang(): string
    {
        return $this->getFileAttribute('target-language');
    }

    public function getIterator(): Traversable
    {
        return new TranslationIterator($this);
    }

    /**
     * Workaround the root namespace problem helper.
     */
    protected function rootNSWorkaround(): bool
    {
        return $this->doc->documentElement->isDefaultNamespace(self::NS);
    }

    /**
     * Set attribute workaround helper.
     *
     * Work around method for the fact that DOMDocument adds some mysterious namespache "xmlns:default"
     * when the root NS is the requested XMLNS and setAttributeNS() is used.
     *
     * @param DOMElement $node  The node to which the attribute shall be written to.
     * @param string     $name  The name of the attribute.
     * @param string     $value The value for the attribute.
     */
    protected function setAttribute(DOMElement $node, string $name, string $value): DOMElement
    {
        if ($this->rootNSWorkaround()) {
            $node->setAttribute($name, $value);
        } else {
            $node->setAttributeNS(self::NS, $name, $value);
        }

        return $node;
    }

    /**
     * Get attribute workaround helper.
     *
     * Work around method for the fact that DOMDocument adds some mysterious namespache "xmlns:default"
     * when the root NS is the requested XMLNS and setAttributeNS() is used.
     *
     * @param DOMElement $node The node from which the attribute shall be read.
     * @param string     $name The name of the attribute.
     */
    protected function getAttribute(DOMElement $node, string $name): string
    {
        if ($this->rootNSWorkaround()) {
            return $node->getAttribute($name);
        }

        return $node->getAttributeNS(self::NS, $name);
    }

    /**
     * Sets the given attribute in the XML element "file" to the given value.
     *
     * @param string $name  The name of the attribute to set.
     * @param string $value The language code from ISO 639-1.
     */
    protected function setFileAttribute(string $name, string $value): void
    {
        $this->setAttribute($this->getFile(), $name, $value);
    }

    /**
     * Gets the given attribute in the XML element "file".
     *
     * @param string $name The name of the attribute to get.
     */
    protected function getFileAttribute(string $name): string
    {
        return $this->getAttribute($this->getFile(), $name);
    }

    /**
     * Creates a new XPath object for the doc with the namespace xliff registered.
     */
    protected function getXPath(): DOMXPath
    {
        $xpath = new DOMXPath($this->doc);

        $xpath->registerNamespace('xliff', self::NS);

        return $xpath;
    }

    /**
     * Perform a Xpath search with the given query and return the first match if found.
     *
     * @param string       $query       The query to use.
     * @param DOMNode|null $contextNode The context node to apply.
     */
    protected function getXPathFirstItem(string $query, ?DOMNode $contextNode = null): ?DOMElement
    {
        $tmp = $this->getXPath()->query($query, $contextNode);
        if (!$tmp instanceof DOMNodeList) {
            return null;
        }

        if ($tmp->length === 0) {
            return null;
        }
        $item = $tmp->item(0);
        assert($item instanceof DOMElement);

        return $item;
    }

    /**
     * Searches for the XMLNode that contains the given id.
     *
     * Optionally, the node can be created if not found.
     *
     * @param string $identifier The id string to search for.
     *
     * @throws RuntimeException When an empty id is queried, an Exception is thrown.
     */
    private function searchOrCreateId(string $identifier): DOMElement
    {
        if ('' === $identifier) {
            throw new RuntimeException('Empty Id passed.');
        }

        $transUnit = $this->searchForId($identifier);

        if (null === $transUnit) {
            $transUnit = $this->createElement('trans-unit');
            $this->getBody()->appendChild($transUnit);
            $this->setAttribute($transUnit, 'id', $identifier);
        }

        return $transUnit;
    }

    /**
     * Searches for the XMLNode that contains the given id.
     *
     * @param string $identifier The id string to search for.
     *
     * @throws RuntimeException When an empty id is queried, an Exception is thrown.
     */
    private function searchForId(string $identifier): ?DOMElement
    {
        if ('' === $identifier) {
            throw new RuntimeException('Empty Id passed.');
        }

        return $this->getXPathFirstItem(
            $this->rootNSWorkaround()
            ? '/xliff:xliff/xliff:file/xliff:body/xliff:trans-unit[@id=\'' . $identifier . '\']'
            : '/xliff:xliff/xliff:file/xliff:body/xliff:trans-unit[@xliff:id=\'' . $identifier . '\']'
        );
    }

    private function getFile(): DOMElement
    {
        if (null === ($file = $this->getXPathFirstItem('/xliff:xliff/xliff:file'))) {
            $file = $this->createElement('file');
            $this->doc->documentElement->appendChild($file);
        }

        return $file;
    }

    private function getBody(): DOMElement
    {
        if (null === ($body = $this->getXPathFirstItem('/xliff:xliff/xliff:file/xliff:body'))) {
            $body = $this->createElement('body');
            $this->getFile()->appendChild($body);
        }

        return $body;
    }

    private function createElement(string $name): DOMElement
    {
        return $this->doc->createElementNS(self::NS, $name);
    }
}
