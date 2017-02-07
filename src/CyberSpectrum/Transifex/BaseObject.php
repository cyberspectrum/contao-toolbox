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

namespace CyberSpectrum\Transifex;

use CyberSpectrum\PhpTransifex\Client;

/**
 * This class provides a base implementation of a transifex object.
 */
class BaseObject
{
    /**
     * The transifex client.
     *
     * @var Client
     */
    private $transport;

    /**
     * Create a new instance.
     *
     * @param Client $transport The transport to use.
     */
    public function __construct(Client $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Retrieve the transport in use.
     *
     * @return Client
     */
    protected function getTransport()
    {
        return $this->transport;
    }

    /**
     * Ensure a parameter exists and return its value.
     *
     * @param string $name The parameter name.
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException When the parameter is missing.
     */
    protected function ensureParameter($name)
    {
        if (!isset($this->$name)) {
            throw new \InvalidArgumentException(get_class($this) . ' is missing parameter: ' . $name);
        }

        return $this->$name;
    }
}
