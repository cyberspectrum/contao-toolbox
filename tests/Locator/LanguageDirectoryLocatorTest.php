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

namespace CyberSpectrum\ContaoToolBox\Test\Locator;

use CyberSpectrum\ContaoToolBox\Locator\LanguageDirectoryLocator;
use CyberSpectrum\ContaoToolBox\Tests\TestCase;

/**
 * This tests the language directory locator.
 */
class LanguageDirectoryLocatorTest extends TestCase
{
    /**
     * Test that the locator finds all directories.
     *
     * @return void
     */
    public function testLocator()
    {
        $tmp = $this->getTempDir();
        mkdir($tmp . DIRECTORY_SEPARATOR . 'en');
        mkdir($tmp . DIRECTORY_SEPARATOR . 'de');
        mkdir($tmp . DIRECTORY_SEPARATOR . 'invalid-directory');
        mkdir($tmp . DIRECTORY_SEPARATOR . 'fr');
        mkdir($tmp . DIRECTORY_SEPARATOR . 'de_DE');

        $locator = new LanguageDirectoryLocator($tmp);

        $result = $locator->determineLanguages();
        sort($result);

        $this->assertSame(['de', 'de_DE', 'en', 'fr'], $result);
    }

    /**
     * Test that the locator finds all directories but filters correctly.
     *
     * @return void
     */
    public function testLocatorFiltered()
    {
        $tmp = $this->getTempDir();
        mkdir($tmp . DIRECTORY_SEPARATOR . 'en');
        mkdir($tmp . DIRECTORY_SEPARATOR . 'de');
        mkdir($tmp . DIRECTORY_SEPARATOR . 'invalid-directory');
        mkdir($tmp . DIRECTORY_SEPARATOR . 'fr');
        mkdir($tmp . DIRECTORY_SEPARATOR . 'de_DE');

        $locator = new LanguageDirectoryLocator($tmp);

        $result = $locator->determineLanguages(['en']);
        sort($result);

        $this->assertSame(['de', 'de_DE', 'fr'], $result);
    }

    /**
     * Test we get an exception for non existent base dirs.
     *
     * @return void
     */
    public function testBailsForNonExistantDirectory()
    {
        $tmp = $this->getTempDir();

        $this->setExpectedException(
            '\InvalidArgumentException',
            'The path ' . $tmp . DIRECTORY_SEPARATOR . 'languages does not exist.'
        );

        $locator = new LanguageDirectoryLocator($tmp . DIRECTORY_SEPARATOR . 'languages');

        $locator->determineLanguages();
    }
}
