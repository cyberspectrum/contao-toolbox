<?php

namespace CyberSpectrum\Command;

use CyberSpectrum\Translation\Contao;
use CyberSpectrum\Translation\Xliff;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


abstract class ConvertBase extends CommandBase
{
	protected $cleanup;

	protected $baseFiles;

	protected function configure()
	{
		parent::configure();
		$this->addOption('cleanup', null, InputOption::VALUE_NONE, 'if set, remove obsolete files.');
	}

	abstract protected function getDestinationBasePath();

	protected function getBaseFiles()
	{
		$iterator = new \DirectoryIterator($this->getLanguageBasePath(). DIRECTORY_SEPARATOR . $this->baselanguage);

		$files = array();
		while ($iterator->valid())
		{
			if (!$iterator->isDot()
                && $iterator->isFile()
                && $this->isValidSourceFile($iterator->getPathname())
                && $this->isNotFileToSkip($iterator->getBasename()))
			{
				$files[] = $iterator->getFilename();
			}
			$iterator->next();
		}

		$this->baseFiles = $files;
	}

	abstract protected function isValidSourceFile($file);

	abstract protected function isValidDestinationFile($file);

	abstract protected function processLanguage(OutputInterface $output, $language);

    protected function isNotFileToSkip($basename)
    {
        return is_array($this->skipFiles) ? !in_array(substr($basename, 0, -4), $this->skipFiles) : true;
    }

	protected function determinePresentFiles($language)
	{
		$iterator = new \DirectoryIterator($this->getDestinationBasePath(). DIRECTORY_SEPARATOR . $language);

		$files = array();
		while ($iterator->valid())
		{
			if (!$iterator->isDot() && $iterator->isFile() && $this->isValidDestinationFile($iterator->getPathname()))
			{
				$files[] = $iterator->getFilename();
			}
			$iterator->next();
		}

		return $files;
	}

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);

		$this->cleanup      = $input->getOption('cleanup');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->getBaseFiles();

		foreach ($this->languages as $lang)
		{
			$this->processLanguage($output, $lang);
		}
	}
}