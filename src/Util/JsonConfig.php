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
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\ContaoToolBox\Util;

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
