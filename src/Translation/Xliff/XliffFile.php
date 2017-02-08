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
use Psr\Log\LoggerInterface;

/**
 * This class represents a XLIFF translation file.
 */
class XliffFile extends AbstractFile
{
    /**
     * The xliff Namespace.
     */
    const NS = 'urn:oasis:names:tc:xliff:document:1.2';

    /**
     * The document we are currently working on.
     *
     * @var \DOMDocument
     */
    protected $doc;

    /**
     * The filename to work on.
     *
     * @var string
     */
    protected $filename;

    /**
     * The datatype to specify in the Xliff file.
     *
     * @var string
     */
    protected $datatype = 'php';

    /**
     * The original data source id - most likely the file name or something like this.
     *
     * @var string
     */
    protected $original;

    /**
     * The source language as ISO 639-1 language code.
     *
     * @var string
     */
    protected $srclang;

    /**
     * The source language as ISO 639-1 language code.
     *
     * @var string
     */
    protected $tgtlang;

    /**
     * The timestamp of last update as unix timestamp.
     *
     * @var int
     */
    protected $date;

    /**
     * Create a new instance.
     *
     * @param string          $filename The filename to use.
     *
     * @param LoggerInterface $logger   The logger to use.
     */
    public function __construct($filename, LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->filename = $filename;

        $this->doc                     = new \DOMDocument('1.0', 'UTF-8');
        $this->doc->preserveWhiteSpace = false;
        $this->doc->formatOutput       = true;

        $this->load($filename);
    }

    /**
     * Save the contents to disk.
     *
     * @return void
     */
    public function save()
    {
        if ($this->filename) {
            $this->doc->save($this->filename);
        }
    }

    /**
     * Load the file from disk.
     *
     * @param string $filename The filename.
     *
     * @return void
     */
    public function load($filename)
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
     *
     * @return void
     */
    public function loadXML($content)
    {
        $this->doc->loadXML($content, LIBXML_NSCLEAN);
    }

    /**
     * Create the basic document structure.
     *
     * @return void
     */
    protected function createBasicDocument()
    {
        $this->doc->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2"><file><body></body></file></xliff>',
            LIBXML_NSCLEAN
        );

        // Set some basic information.
        $this->setDataType('plaintext');
        $this->setDate(time());
        $this->setOriginal('');
        $this->setSrcLang('en');
        $this->setTgtLang('en');
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
     * Set the datatype in this file.
     *
     * See http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#datatype
     *
     * You may use a custom datatype here but have to prefix it with "x-".
     *
     * @param string $datatype The data type.
     *
     * @return void
     */
    public function setDataType($datatype)
    {
        $this->setFileAttribute('datatype', $datatype);
    }

    /**
     * Get the datat ype for this file.
     *
     * See http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#datatype
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->getFileAttribute('datatype');
    }

    /**
     * Sets the last modification time in this file.
     *
     * @param int $date Timestamp.
     *
     * @return void
     */
    public function setDate($date)
    {
        $this->setFileAttribute('date', date('c', $date));
    }

    /**
     * Return the last modification time from this file as timestamp.
     *
     * @return int
     */
    public function getDate()
    {
        return strtotime($this->getFileAttribute('date'));
    }

    /**
     * Set the "original" data source id value in the file.
     *
     * You will most likely the file name of the original resource or something like this here.
     *
     * @param string $original The name of the original data source.
     *
     * @return void
     */
    public function setOriginal($original)
    {
        $this->setFileAttribute('original', $original);
    }

    /**
     * Get the original resource name from this file.
     *
     * @return string
     */
    public function getOriginal()
    {
        return $this->getFileAttribute('original');
    }

    /**
     * Set the source language for this file.
     *
     * @param string $srclang The language code from ISO 639-1.
     *
     * @return void
     */
    public function setSrcLang($srclang)
    {
        $this->setFileAttribute('source-language', $srclang);
    }

    /**
     * Get the current source language for this file.
     *
     * @return string The language code from ISO 639-1
     */
    public function getSrcLang()
    {
        return $this->getFileAttribute('source-language');
    }

    /**
     * Set the target language for this file.
     *
     * @param string $tgtlang The language code from ISO 639-1.
     *
     * @return void
     */
    public function setTgtLang($tgtlang)
    {
        $this->setFileAttribute('target-language', $tgtlang);
    }

    /**
     * Get the current target language for this file.
     *
     * @return string The language code from ISO 639-1.
     */
    public function getTgtLang()
    {
        return $this->getFileAttribute('target-language');
    }

    /**
     * Workaround the root namespace problem helper.
     *
     * @return bool
     */
    protected function rootNSWorkaround()
    {
        return $this->doc->documentElement->isDefaultNamespace(self::NS);
    }

    /**
     * Set attribute workaround helper.
     *
     * Work around method for the fact that DOMDocument adds some mysterious namespache "xmlns:default"
     * when the root NS is the requested XMLNS and setAttributeNS() is used.
     *
     * @param \DOMElement $node  The node to which the attribute shall be written to.
     *
     * @param string      $name  The name of the attribute.
     *
     * @param string      $value The value for the attribute.
     *
     * @return \DOMElement
     */
    protected function setAttribute(\DOMElement $node, $name, $value)
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
     * @param \DOMElement $node The node from which the attribute shall be read.
     *
     * @param string      $name The name of the attribute.
     *
     * @return string
     */
    protected function getAttribute(\DOMElement $node, $name)
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
     *
     * @param string $value The language code from ISO 639-1.
     *
     * @return void
     */
    protected function setFileAttribute($name, $value)
    {
        $file = $this->getXPathFirstItem('/xliff:xliff/xliff:file');
        $this->setAttribute($file, $name, $value);
    }

    /**
     * Gets the given attribute in the XML element "file".
     *
     * @param string $name The name of the attribute to get.
     *
     * @return string
     */
    protected function getFileAttribute($name)
    {
        $file = $this->getXPathFirstItem('/xliff:xliff/xliff:file');

        return $this->getAttribute($file, $name);
    }

    /**
     * Creates a new XPath object for the doc with the namespace xliff registered.
     *
     * @return \DOMXPath
     */
    protected function getXPath()
    {
        $xpath = new \DOMXPath($this->doc);

        $xpath->registerNamespace('xliff', self::NS);

        return $xpath;
    }

    /**
     * Perform a Xpath search with the given query and return the first match if found.
     *
     * @param string $query       The query to use.
     *
     * @param null   $contextnode The context node to apply.
     *
     * @return \DOMElement|\DOMNode|null
     */
    protected function getXPathFirstItem($query, $contextnode = null)
    {
        /** @var \DOMNodeList $tmp */
        $tmp = $this->getXPath()->query($query, $contextnode);

        return $tmp->length ? $tmp->item(0) : null;
    }

    /**
     * Searches for the XMLNode that contains the given id.
     *
     * Optionally, the node can be created if not found.
     *
     * @param string $identifier The id string to search for.
     *
     * @param bool   $create     If true, an element with the given Id will be created if none has been found.
     *
     * @return \DOMNode
     *
     * @throws \Exception When an empty Id is queried, an Exception is thrown.
     */
    protected function searchForId($identifier, $create = false)
    {
        if (!strlen($identifier)) {
            throw new \Exception('Empty Id passed.');
        }

        /** @var \DOMNodeList $transUnits */
        if ($this->rootNSWorkaround()) {
            $transUnit = $this->getXPathFirstItem(
                '/xliff:xliff/xliff:file/xliff:body/xliff:trans-unit[@id=\'' . $identifier . '\']'
            );
        } else {
            $transUnit = $this->getXPathFirstItem(
                '/xliff:xliff/xliff:file/xliff:body/xliff:trans-unit[@xliff:id=\'' . $identifier . '\']'
            );
        }

        if ($create && ($transUnit === null)) {
            $body = $this->getXPathFirstItem('/xliff:xliff/xliff:file/xliff:body');

            /** @var $transUnit \DOMElement */
            $transUnit = $this->doc->createElementNS(self::NS, 'trans-unit');

            $body->appendChild($transUnit);

            $this->setAttribute($transUnit, 'id', $identifier);
        }

        return $transUnit;
    }

    /**
     * Sets the source value for the given key to the given value.
     *
     * @param string $key   The key for the translation string.
     *
     * @param string $value The value to use.
     *
     * @return void
     */
    public function setSource($key, $value)
    {
        $unit = $this->searchForId($key, true);

        $source = $this->getXPathFirstItem('xliff:source', $unit);

        // If already present check
        if ($source === null) {
            $source = $unit->appendChild($this->doc->createElementNS(self::NS, 'source'));
        } elseif ($source->firstChild) {
            // If already present, we have to remove the textnode if one exists as otherwise the value will get
            // appended.
            $source->removeChild($source->firstChild);
        }

        $source->appendChild($this->doc->createTextNode($value));
    }

    /**
     * Return the source value for a key.
     *
     * @param string $key The key to search for.
     *
     * @return null|string
     */
    public function getSource($key)
    {
        $unit = $this->searchForId($key);

        $source = $this->getXPathFirstItem('xliff:source', $unit);
        if ($source && $source->firstChild) {
            return $source->firstChild->nodeValue;
        }

        return null;
    }

    /**
     * Sets the target value for the given key to the given value.
     *
     * @param string $key   The key for the translation string.
     *
     * @param string $value The value to use.
     *
     * @return void
     */
    public function setTarget($key, $value)
    {
        $unit = $this->searchForId($key, true);

        $target = $this->getXPathFirstItem('xliff:target', $unit);

        // If already present check.
        if ($target === null) {
            $target = $unit->appendChild($this->doc->createElementNS(self::NS, 'target'));
        } elseif ($target->firstChild) {
            // If already present, we have to remove the textnode if one exists as otherwise the value will get
            // appended.
            $target->removeChild($target->firstChild);
        }

        $target->appendChild($this->doc->createTextNode($value));
    }

    /**
     * Return the target value for a key.
     *
     * @param string $key The key to search for.
     *
     * @return null|string
     */
    public function getTarget($key)
    {
        $unit = $this->searchForId($key);

        $target = $this->getXPathFirstItem('xliff:target', $unit);
        if ($target && $target->firstChild) {
            return $target->firstChild->nodeValue;
        }

        return null;
    }

    /**
     * Search for an entry with the given id and remove it if found.
     *
     * @param string $key The language key to be searched.
     *
     * @return XliffFile
     */
    public function remove($key)
    {
        $unit = $this->searchForId($key);
        if ($unit) {
            $unit->parentNode->removeChild($unit);
        }

        return $this;
    }

    /**
     * Retrieve all the keys.
     *
     * @return array
     *
     * @throws \Exception When an id is empty.
     */
    public function getKeys()
    {
        /** @var \DOMNodeList $tmp */
        $transUnits = $this->getXPath()->query('/xliff:xliff/xliff:file/xliff:body/xliff:trans-unit');

        $result = array();

        if ($transUnits->length > 0) {
            /** @var \DOMElement $element */
            foreach ($transUnits as $element) {
                if (!$this->getAttribute($element, 'id')) {
                    throw new \Exception('Empty Id: ' . var_export($element, true));
                }
                $result[] = (string) $this->getAttribute($element, 'id');
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new TranslationIterator($this);
    }
}
