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

namespace CyberSpectrum\ContaoToolBox\Tests;

use Symfony\Component\Filesystem\Filesystem;

/**
 * This class is the test base.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Temporary working dir.
     *
     * @var string
     */
    private $workDir;

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        if (null !== $this->workDir) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->workDir);
        }
    }

    /**
     * Retrieve the path to the fixtures.
     *
     * @return string
     */
    protected function getFixturesPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
    }

    /**
     * Create and return the path to a temp dir.
     *
     * @return string
     */
    protected function getTempDir()
    {
        if (null === $this->workDir) {
            $temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('contao-toolbox-test', false);
            mkdir($temp, 0777, true);
            $this->workDir = $temp;
        }

        return $this->workDir;
    }

    /**
     * Retrieve the path of a temp file within the temp dir of the test.
     *
     * @param string $name             Optional name of the file.
     *
     * @param bool   $forceDirectories Optional flag if the parenting dirs should be created.
     *
     * @return string
     */
    public function getTempFile($name = '', $forceDirectories = true)
    {
        if ('' === $name) {
            $name = uniqid('', false);
        }

        $path = $this->getTempDir() . DIRECTORY_SEPARATOR . $name;

        if ($forceDirectories && !is_dir($dir = dirname($path))) {
            mkdir($dir, 0777, true);
        }

        return $path;
    }
}
