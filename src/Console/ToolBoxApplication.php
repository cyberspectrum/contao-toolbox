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

namespace CyberSpectrum\ContaoToolBox\Console;

use CyberSpectrum\ContaoToolBox\Console\Command\CleanUpTx;
use CyberSpectrum\ContaoToolBox\Console\Command\Convert\ConvertFromXliff;
use CyberSpectrum\ContaoToolBox\Console\Command\Convert\ConvertToXliff;
use CyberSpectrum\ContaoToolBox\Console\Command\Transifex\DownloadTransifex;
use CyberSpectrum\ContaoToolBox\Console\Command\Transifex\UploadTransifex;
use CyberSpectrum\ContaoToolBox\Util\JsonConfig;
use RuntimeException;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function chdir;
use function defined;
use function file_exists;
use function getcwd;
use function getenv;
use function is_string;
use function rtrim;
use function str_replace;

/**
 * This class implements the main application of the toolbox.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ToolBoxApplication extends BaseApplication
{
    /**
     * The application home dir.
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private string $home;

    protected function getDefaultCommands(): array
    {
        return array(
            new ConvertFromXliff(),
            new ConvertToXliff(),
            new DownloadTransifex(),
            new UploadTransifex(),
            new CleanUpTx(),
            new HelpCommand(),
            new ListCommand(),
        );
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $result = parent::getDefaultInputDefinition();
        $result->addOption(
            new InputOption(
                '--working-dir',
                '-d',
                InputOption::VALUE_REQUIRED,
                'If specified, use the given directory as working directory.'
            )
        );

        return $result;
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        if (null !== ($newWorkDir = $this->getNewWorkingDir($input))) {
            $oldWorkingDir = getcwd();
            chdir($newWorkDir);
        }

        $result = parent::doRun($input, $output);

        if (isset($oldWorkingDir) && is_string($oldWorkingDir)) {
            chdir($oldWorkingDir);
        }

        return $result;
    }

    /**
     * Detect the new working dir.
     *
     * @param InputInterface $input The input instance.
     *
     * @throws RuntimeException When the working dir is invalid.
     */
    private function getNewWorkingDir(InputInterface $input): ?string
    {
        $workingDir = $input->getParameterOption(['--working-dir', '-d']);
        if (false === $workingDir) {
            return null;
        }
        if (!is_string($workingDir) || !is_dir($workingDir)) {
            throw new RuntimeException('Invalid working directory specified.');
        }

        return $workingDir;
    }

    /**
     * Determine the home directory where cbt config files shall be stored.
     *
     * @throws RuntimeException When neither a valid home dir nor the CBT_HOME environment variables are defined.
     */
    protected function getHome(): string
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->home)) {
            return $this->home;
        }
        // determine home dir
        $home = getenv('CBT_HOME');
        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                $appData = getenv('APPDATA');
                if (empty($appData)) {
                    throw new RuntimeException(
                        'The APPDATA or CBT_HOME environment variable must be set for cbt to run correctly'
                    );
                }
                $home = str_replace('\\', '/', $appData) . '/CBT';
            } else {
                $home = getenv('HOME');
                if (empty($home)) {
                    throw new RuntimeException(
                        'The HOME or CBT_HOME environment variable must be set for cbt to run correctly'
                    );
                }
                $home = rtrim($home, '/') . '/.config/ctb';
            }
        }

        $this->home = $home;

        return $home;
    }

    /**
     * Retrieve a config instance.
     */
    public function getConfig(): ?JsonConfig
    {
        $dir = $this->getHome();
        if (!file_exists($dir . '/config.json')) {
            return null;
        }

        return new JsonConfig($dir . '/config.json');
    }
}
