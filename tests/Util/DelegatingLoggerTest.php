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

namespace CyberSpectrum\ContaoToolBox\Tests\Util;

use CyberSpectrum\ContaoToolBox\Tests\TestCase;
use CyberSpectrum\ContaoToolBox\Util\DelegatingLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * This tests the delegating logger.
 */
class DelegatingLoggerTest extends TestCase
{
    /**
     * Test that a log call gets delegated.
     */
    public function testDelegatesLogCall(): void
    {
        $mockLog = $this->getMockForAbstractClass(LoggerInterface::class);
        $mockLog->expects($this->once())->method('log')->with(LogLevel::ALERT, 'Whoopsie!', ['context' => 'value']);

        $logger = new DelegatingLogger($mockLog);
        $logger->alert('Whoopsie!', ['context' => 'value']);
    }

    /**
     * Test that logging works when no logger is available.
     */
    public function testIgnoresLogCallWithoutLogger(): void
    {
        $logger = new DelegatingLogger(null);
        $logger->alert('Whoopsie!', ['context' => 'value']);
        $this->addToAssertionCount(1);
    }
}
