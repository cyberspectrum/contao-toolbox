<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author       Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum;

/**
 * This class represents a simple json config.
 */
class JsonConfig
{
    /**
     * The array read from the json file.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new instance.
     *
     * @param string $filename The filename.
     *
     * @throws \InvalidArgumentException When a parsing error occured.
     */
    public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException('Error: file not found: ' . $filename);
        }

        $this->data = json_decode(file_get_contents($filename), true);

        if ($this->data === null) {
            throw new \InvalidArgumentException(sprintf('Error parsing %s - %s', $filename, json_last_error()));
        }
    }

    /**
     * Scan to a given value.
     *
     * @param string $path  The path.
     *
     * @param array  $scope The current array scope.
     *
     * @return null|array|string|int
     */
    protected function scanTo($path, $scope)
    {
        if (!($sub = array_shift($path)) || !isset($scope[$sub])) {
            return null;
        }

        if (!$path) {
            return $scope[$sub];
        }

        return $this->scanTo($path, $scope[$sub]);
    }

    /**
     * Retrieve a config value.
     *
     * @param string $path The path.
     *
     * @return null|array|string|int
     */
    public function getConfigValue($path)
    {
        $chunks = explode('/', $path);

        if (!$chunks[0]) {
            array_shift($chunks);
        }

        return $this->scanTo($chunks, $this->data);
    }
}
