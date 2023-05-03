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

namespace CyberSpectrum\ContaoToolBox\Tests\Translation\Contao;

use CyberSpectrum\ContaoToolBox\Tests\TestCase;
use CyberSpectrum\ContaoToolBox\Translation\Contao\ContaoFile;

/**
 * This class tests the Contao file parser.
 */
class ParserTest extends TestCase
{
    /**
     * Test the parsing.
     */
    public function testParseEmpty(): void
    {
        $file = new ContaoFile($this->getFixturesPath() . 'contao-parse1-empty-file.php');
        $this->assertEmpty($file->keys());
    }

    /**
     * Test the parsing.
     */
    public function testParseFilled(): void
    {
        $file = new ContaoFile($this->getFixturesPath() . 'contao-parse2-filled.php');
        $this->assertCount(12, $file->keys());
        $this->assertEquals('a-a-1', $file->get('a.a.0'));
        $this->assertEquals('a-a-2', $file->get('a.a.1'));
        $this->assertEquals('a-b-1', $file->get('a.b.0'));
        $this->assertEquals('a-b-2', $file->get('a.b.1'));
        $this->assertEquals('a-c-1', $file->get('a.c.0'));
        $this->assertEquals('a-c-2', $file->get('a.c.1'));
        $this->assertEquals('a-d-1', $file->get('a.d.0'));
        $this->assertEquals('a-d-2', $file->get('a.d.1'));
        $this->assertEquals('a-e-1', $file->get('a.e.0'));
        $this->assertEquals('a-e-2', $file->get('a.e.1'));
        $this->assertEquals('a-f-1', $file->get('a.f.0'));
        $this->assertEquals('a-f-2', $file->get('a.f.1'));
    }

    /**
     * Test the parsing.
     */
    public function testParseShortArray(): void
    {
        $file = new ContaoFile($this->getFixturesPath() . 'contao-parse3-short-array.php');
        $this->assertCount(5, $file->keys());
        $this->assertEquals('a-a-1', $file->get('a.a.0'));
        $this->assertEquals('a-a-2', $file->get('a.a.1'));
        $this->assertEquals('a-b-1', $file->get('a.b.0'));
        $this->assertEquals('a-b-2', $file->get('a.b.1'));
        $this->assertEquals('a-b-c', $file->get('a.b.c'));
    }
}
