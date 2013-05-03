<?php


namespace CyberSpectrum\Command;

use CyberSpectrum\JsonConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


abstract class CommandBase extends Command
{
	protected $project;

	protected $prefix;

	protected $txlang;

	protected $ctolang;

	protected $baselanguage;

	protected $languages;

	protected function configure()
	{
		parent::configure();

		$this->addOption('contao', 'c', InputOption::VALUE_REQUIRED, 'Contao language root directory (base to "en","de" etc.), if empty it will get read from the composer.json.', null);
		$this->addOption('xliff', 'x', InputOption::VALUE_OPTIONAL, 'Xliff root directory (base to "en","de" etc.), if empty it will get read from the composer.json.', null);
		$this->addOption('projectname', 'p', InputOption::VALUE_OPTIONAL, 'The project name, if empty it will get read from the composer.json.', null);
		$this->addOption('prefix', null, InputOption::VALUE_OPTIONAL, 'The prefix for all language files, if empty it will get read from the composer.json.', null);
		$this->addOption('base-language', 'b', InputOption::VALUE_OPTIONAL, 'The base language to use.', 'en');

		$this->addArgument('languages', InputArgument::OPTIONAL, 'Languages to process as comma delimited list or "all" for all languages.', 'all');
	}

	protected function getConfigValue($name)
	{
		$config = new JsonConfig(getcwd() . '/composer.json');

		return $config->getConfigValue($name);
	}

	abstract protected function getLanguageBasePath();

	protected function determineLanguages($srcdir, $filter = array())
	{
		if (!is_dir($srcdir))
		{
			throw new \InvalidArgumentException(sprintf('The path %s does not exist.', $srcdir));
		}

		$matches = array();
		$iterator = new \DirectoryIterator($srcdir);
		do
		{
			$item = $iterator->getFilename();

			if ((!$iterator->isDot()) && (strlen($item) == 2) && ((!$filter) || in_array($item, $filter)))
			{
				$matches[] = $item;
			}
			$iterator->next();
		}
		while ($iterator->valid());

		$this->languages = $matches;
	}

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->project      = $input->getOption('projectname');
		$this->prefix       = $input->getOption('prefix');
		$this->txlang       = $input->getOption('xliff');
		$this->ctolang      = $input->getOption('contao');
		$this->baselanguage = $input->getOption('base-language');

		if (!$this->project)
		{
			$this->project = $this->getConfigValue('/extra/contao/transifex/project');
			if (!$this->project)
			{
				throw new \RuntimeException('Error: unable to determine transifex project name.');
			}

			$output->writeln(sprintf('automatically using project: %s', $this->project));
		}

		if (!$this->prefix)
		{
			$this->prefix = $this->getConfigValue('/extra/contao/transifex/prefix');

			if (!$this->prefix)
			{
				throw new \RuntimeException('Error: unable to determine transifex prefix.');
			}
			$output->writeln(sprintf('automatically using prefix: %s', $this->prefix));
		}

		if (!$this->txlang)
		{
			$this->txlang = $this->getConfigValue('/extra/contao/transifex/languages_tx');

			if (!$this->txlang)
			{
				throw new \RuntimeException('Error: unable to determine transifex root folder.');
			}
			$output->writeln(sprintf('automatically using xliff folder: %s', $this->txlang));
		}

		if (!$this->ctolang)
		{
			$this->ctolang = $this->getConfigValue('/extra/contao/transifex/languages_cto');

			if (!$this->ctolang)
			{
				throw new \RuntimeException('Error: unable to determine contao language root folder.');
			}
			$output->writeln(sprintf('automatically using Contao language folder: %s', $this->ctolang));
		}

		$activeLanguages = array();
		if (($langs = $input->getArgument('languages')) != 'all')
		{
			$activeLanguages = explode(',', $langs);
		}

		$this->determineLanguages($this->getLanguageBasePath(), $activeLanguages);
	}

}