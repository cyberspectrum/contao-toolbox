<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Translation;

/**
 * This class provides an abstract base implementation of translation files.
 */
abstract class AbstractFile implements \IteratorAggregate
{
    /**
     * Debug flag.
     *
     * @var bool
     */
    private $debug = false;

    /**
     * The debug messages.
     *
     * @var string[]
     */
    private $debugMessages = array();

    /**
     * Create a new instance.
     *
     * @param bool $debug The debug flag. True to enable debugging, false otherwise.
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Retrieve a list of all language keys.
     *
     * @return array
     */
    abstract public function getKeys();

    /**
     * Write a debug message.
     *
     * @param string $message The message.
     *
     * @return AbstractFile
     */
    public function debug($message)
    {
        if ($this->debug) {
            $this->debugMessages[] = $message;
        }

        return $this;
    }

    /**
     * Enable or disable debugging.
     *
     * @param bool $enabled The new value for debugging.
     *
     * @return AbstractFile
     */
    public function setDebugging($enabled = true)
    {
        $this->debug = $enabled;

        return $this;
    }

    /**
     * Retrieve the debug messages.
     *
     * @return string[]
     */
    public function getDebugMessages()
    {
        return $this->debugMessages;
    }
}
