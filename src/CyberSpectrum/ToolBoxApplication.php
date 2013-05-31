<?php


namespace CyberSpectrum;

use Symfony\Component\Console;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CyberSpectrum\Command\ConvertFromXliff;
use CyberSpectrum\Command\ConvertToXliff;
use CyberSpectrum\Command\DownloadTransifex;
use CyberSpectrum\Command\UploadTransifex;

class ToolBoxApplication extends BaseApplication
{
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
			new HelpCommand(),
			new ListCommand(),
		);
	}

	protected function getDefaultInputDefinition()
	{
		$result = parent::getDefaultInputDefinition();
		$result->addOption(new InputOption('--working-dir', '-d', InputOption::VALUE_REQUIRED, 'If specified, use the given directory as working directory.'));
		return $result;
	}

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
	 * @param  InputInterface    $input
	 *
	 * @throws \RuntimeException
	 */
	private function getNewWorkingDir(InputInterface $input)
	{
		$workingDir = $input->getParameterOption(array('--working-dir', '-d'));
		if (false !== $workingDir && !is_dir($workingDir))
		{
			throw new \RuntimeException('Invalid working directory specified.');
		}

		return $workingDir;
	}

	/**
	 * Determine the home directory where cbt config files shall be stored.
	 *
	 * @throws \RuntimeException
	 */
	protected function getHome()
	{
		if (isset($this->home))
		{
			return $this->home;
		}
		// determine home dir
		$home = getenv('CBT_HOME');
		if (!$home) {
			if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
				if (!getenv('APPDATA')) {
					throw new \RuntimeException('The APPDATA or CBT_HOME environment variable must be set for cbt to run correctly');
				}
				$home = strtr(getenv('APPDATA'), '\\', '/') . '/CBT';
			} else {
				if (!getenv('HOME')) {
					throw new \RuntimeException('The HOME or CBT_HOME environment variable must be set for cbt to run correctly');
				}
				$home = rtrim(getenv('HOME'), '/') . '/.config/ctb';
			}
		}

		$this->home = $home;

		return $home;
	}

	public function getConfig()
	{
		$dir = $this->getHome();
		if (!file_exists($dir . '/config.json'))
		{
			return null;
		}
		return new JsonConfig($dir . '/config.json');
	}

}