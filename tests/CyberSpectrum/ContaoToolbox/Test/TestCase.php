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

namespace CyberSpectrum\ContaoToolbox\Test;

/**
 * This class is the test base.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Retrieve the path to the fixtures.
     *
     * @return string
     */
    protected function getFixturesPath()
    {
        return dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
    }
}
