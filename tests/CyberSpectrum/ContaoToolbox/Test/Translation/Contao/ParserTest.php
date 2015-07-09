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

namespace CyberSpectrum\ContaoToolbox\Test\Translation\Contao;

use CyberSpectrum\ContaoToolbox\Test\TestCase;
use CyberSpectrum\Translation\Contao\ContaoFile;

/**
 * This class tests the Contao file parser.
 */
class ParserTest extends TestCase
{
    /**
     * Test the parsing.
     *
     * @return void
     */
    public function testParse()
    {
        $file = new ContaoFile($this->getFixturesPath() . 'contao-parse1-empty-file.php');
        $this->assertEmpty($file->getKeys());

        $file = new ContaoFile($this->getFixturesPath() . 'contao-parse2-filled.php');
        $this->assertEquals(12, count($file->getKeys()));
        $this->assertEquals('a-a-1', $file->getValue('a.a.0'));
        $this->assertEquals('a-a-2', $file->getValue('a.a.1'));
        $this->assertEquals('a-b-1', $file->getValue('a.b.0'));
        $this->assertEquals('a-b-2', $file->getValue('a.b.1'));
        $this->assertEquals('a-c-1', $file->getValue('a.c.0'));
        $this->assertEquals('a-c-2', $file->getValue('a.c.1'));
        $this->assertEquals('a-d-1', $file->getValue('a.d.0'));
        $this->assertEquals('a-d-2', $file->getValue('a.d.1'));
        $this->assertEquals('a-e-1', $file->getValue('a.e.0'));
        $this->assertEquals('a-e-2', $file->getValue('a.e.1'));
    }
}
