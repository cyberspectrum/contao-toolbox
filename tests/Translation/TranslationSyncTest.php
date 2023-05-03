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

namespace CyberSpectrum\ContaoToolBox\Tests\Translation;

use CyberSpectrum\ContaoToolBox\Tests\TestCase;
use CyberSpectrum\ContaoToolBox\Translation\Base\TranslationFileInterface;
use CyberSpectrum\ContaoToolBox\Translation\TranslationSync;

/**
 * This tests the TranslationSync
 */
class TranslationSyncTest extends TestCase
{
    /**
     * Test that the synchronization works.
     *
     * This tests that only changed values are really set.
     */
    public function testSync(): void
    {
        $source = $this->getMockForAbstractClass(TranslationFileInterface::class);
        $source->expects($this->once())->method('keys')->willReturn(['translation-key', 'another-key']);
        $source
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['translation-key'], ['another-key'])
            ->willReturnOnConsecutiveCalls('translation-value', 'another-value');

        $destination = $this->getMockForAbstractClass(TranslationFileInterface::class);
        $destination
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['translation-key'], ['another-key'])
            ->willReturnOnConsecutiveCalls('translation-value', null);
        $destination
            ->expects($this->once())
            ->method('set')
            ->with('another-key', 'another-value');

        $sync = new TranslationSync($source, $destination);

        $sync->sync();
    }

    /** Test that the cleanup works. */
    public function testCleanUp(): void
    {
        $source = $this->getMockForAbstractClass(TranslationFileInterface::class);
        $source->expects($this->once())->method('keys')->willReturn(['translation-key']);

        $destination = $this->getMockForAbstractClass(TranslationFileInterface::class);
        $destination->expects($this->once())->method('keys')->willReturn(['translation-key', 'another-key']);
        $destination
            ->expects($this->once())
            ->method('remove')
            ->with('another-key');

        $sync = new TranslationSync($source, $destination);

        $sync->cleanUp();
    }

    /** Test the sync from method. */
    public function testSyncFrom(): void
    {
        $source = $this->getMockForAbstractClass(TranslationFileInterface::class);
        $source->expects($this->exactly(2))->method('keys')->willReturn(['translation-key', 'another-key']);
        $source
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['translation-key'], ['another-key'])
            ->willReturnOnConsecutiveCalls('translation-value', 'another-value');

        $destination = $this->getMockForAbstractClass(TranslationFileInterface::class);
        $destination
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['translation-key'], ['another-key'])
            ->willReturnOnConsecutiveCalls('translation-value', 'old-value');
        $destination
            ->expects($this->once())
            ->method('set')
            ->with('another-key', 'another-value');
        $destination
            ->expects($this->once())
            ->method('keys')
            ->willReturn(['translation-key', 'another-key', 'obsolete']);
        $destination
            ->expects($this->once())
            ->method('remove')
            ->with('obsolete');

        TranslationSync::syncFrom($source, $destination);
    }

    /** Test the sync from method. */
    public function testSyncFromWithoutCleanup(): void
    {
        $source = $this->getMockForAbstractClass(TranslationFileInterface::class);
        $source->expects($this->once())->method('keys')->willReturn(['translation-key', 'another-key']);
        $source
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['translation-key'], ['another-key'])
            ->willReturnOnConsecutiveCalls('translation-value', 'another-value');

        $destination = $this->getMockForAbstractClass(TranslationFileInterface::class);
        $destination
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['translation-key'], ['another-key'])
            ->willReturnOnConsecutiveCalls('translation-value', 'old-value');
        $destination
            ->expects($this->once())
            ->method('set')
            ->with('another-key', 'another-value');
        $destination
            ->expects($this->never())
            ->method('keys');
        $destination
            ->expects($this->never())
            ->method('remove');

        TranslationSync::syncFrom($source, $destination, false);
    }
}
