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
use Symfony\Component\Filesystem\Filesystem;

final class ContaoFileTest extends TestCase
{
    public function testParseAndDumpFile(): void
    {
        $originalFile = $this->getFixturesPath() . 'complete-file.php';
        $outputFile = $this->getTempFile() . '.php';
        (new Filesystem())->copy($originalFile, $outputFile);

        $file = new ContaoFile($outputFile);
        $file->setFileHeader(
            [
                'This file has a header that shall be preserved and updated during each and every run.',
                '',
                'The header even has multiple lines.',
                '',
                '@author    Christian Schiffler <c.schiffler@cyberspectrum.de>',
                '@copyright 2013-2023 CyberSpectrum.',
                '@license   https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0',
            ]
        );
        $file->save();

        self::assertFileEquals($originalFile, $outputFile);
    }

    public function testPreserveFileHeader(): void
    {
        $originalFile = $this->getFixturesPath() . 'complete-file.php';
        $outputFile = $this->getTempFile() . '.php';
        (new Filesystem())->copy($originalFile, $outputFile);

        $file = new ContaoFile($outputFile);
        $file->save();

        self::assertFileEquals($originalFile, $outputFile);
    }
}
