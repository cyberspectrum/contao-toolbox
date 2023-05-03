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

use InvalidArgumentException;

use function array_shift;
use function explode;
use function file_exists;
use function is_array;
use function json_decode;
use function json_last_error;
use function sprintf;

/**
 * This class represents a simple json config.
 */
class JsonConfig
{
    /**
     * The array read from the json file.
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Create a new instance.
     *
     * @param string $filename The filename.
     *
     * @throws InvalidArgumentException When a parsing error occured.
     */
    public function __construct(string $filename)
    {
        if (!file_exists($filename)) {
            throw new InvalidArgumentException('Error: file not found: ' . $filename);
        }

        $data = json_decode(file_get_contents($filename), true);

        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('Error parsing %s - %s', $filename, json_last_error()));
        }
        /** @var array<string, mixed> $data */
        $this->data = $data;
    }

    /**
     * Scan to a given value.
     *
     * @param list<string>         $path  The paths.
     * @param array<string, mixed> $scope The current array scope.
     */
    protected function scanTo(array $path, array $scope): mixed
    {
        if (!($sub = array_shift($path)) || null === ($value = $scope[$sub] ?? null)) {
            return null;
        }
        if (empty($path)) {
            return $value;
        }

        if (!is_array($value)) {
            return null;
        }
        /** @var array<string, mixed> $value */

        return $this->scanTo($path, $value);
    }

    /**
     * Retrieve a config value.
     *
     * @param string $path The path.
     */
    public function getConfigValue(string $path): mixed
    {
        $chunks = explode('/', $path);

        if ('' === $chunks[0]) {
            array_shift($chunks);
        }

        return $this->scanTo($chunks, $this->data);
    }
}
