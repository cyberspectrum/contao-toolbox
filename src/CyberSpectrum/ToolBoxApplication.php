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
}