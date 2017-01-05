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

namespace CyberSpectrum;

use CyberSpectrum\Command\CleanUpTx;
use CyberSpectrum\Command\ConvertFromXliff;
use CyberSpectrum\Command\ConvertToXliff;
use CyberSpectrum\Command\Transifex\DownloadTransifex;
use CyberSpectrum\Command\Transifex\UploadTransifex;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class implements the main application of the toolbox.
 */
class ToolBoxApplication extends BaseApplication
{
    /**
     * The application home dir.
     *
     * @var string
     */
    protected $home;

    /**
     * {@inheritDoc}
     */
    protected function getDefaultCommands()
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

    /**
     * {@inheritDoc}
     */
    protected function getDefaultInputDefinition()
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

    /**
     * {@inheritDoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if ($newWorkDir = $this->getNewWorkingDir($input)) {
            $oldWorkingDir = getcwd();
            chdir($newWorkDir);
        }

        $result = parent::doRun($input, $output);

        if (isset($oldWorkingDir)) {
            chdir($oldWorkingDir);
        }

        return $result;
    }

    /**
     * Detect the new working dir.
     *
     * @param InputInterface $input The input instance.
     *
     * @return string
     *
     * @throws \RuntimeException When the working dir is invalid.
     */
    private function getNewWorkingDir(InputInterface $input)
    {
        $workingDir = $input->getParameterOption(
            array(
                '--working-dir',
                '-d'
            )
        );
        if ((false !== $workingDir) && !is_dir($workingDir)) {
            throw new \RuntimeException('Invalid working directory specified.');
        }

        return $workingDir;
    }

    /**
     * Determine the home directory where cbt config files shall be stored.
     *
     * @return string
     *
     * @throws \RuntimeException When neither a valid home dir nor the CBT_HOME environment variables are defined.
     */
    protected function getHome()
    {
        if (isset($this->home)) {
            return $this->home;
        }
        // determine home dir
        $home = getenv('CBT_HOME');
        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if (!getenv('APPDATA')) {
                    throw new \RuntimeException(
                        'The APPDATA or CBT_HOME environment variable must be set for cbt to run correctly'
                    );
                }
                $home = strtr(getenv('APPDATA'), '\\', '/') . '/CBT';
            } else {
                if (!getenv('HOME')) {
                    throw new \RuntimeException(
                        'The HOME or CBT_HOME environment variable must be set for cbt to run correctly'
                    );
                }
                $home = rtrim(getenv('HOME'), '/') . '/.config/ctb';
            }
        }

        $this->home = $home;

        return $home;
    }

    /**
     * Retrieve the config instance.
     *
     * @return JsonConfig|null
     */
    public function getConfig()
    {
        $dir = $this->getHome();
        if (!file_exists($dir . '/config.json')) {
            return null;
        }

        return new JsonConfig($dir . '/config.json');
    }
}
