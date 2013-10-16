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

    protected $skipFiles;

	protected function configure()
	{
		parent::configure();

		$this->addOption('contao', 'c', InputOption::VALUE_REQUIRED, 'Contao language root directory (base to "en","de" etc.), if empty it will get read from the composer.json.', null);
		$this->addOption('xliff', 'x', InputOption::VALUE_OPTIONAL, 'Xliff root directory (base to "en","de" etc.), if empty it will get read from the composer.json.', null);
		$this->addOption('projectname', 'p', InputOption::VALUE_OPTIONAL, 'The project name, if empty it will get read from the composer.json.', null);
		$this->addOption('prefix', null, InputOption::VALUE_OPTIONAL, 'The prefix for all language files, if empty it will get read from the composer.json.', null);
		$this->addOption('base-language', 'b', InputOption::VALUE_OPTIONAL, 'The base language to use.', 'en');
		$this->addOption('skip-files', 's', InputOption::VALUE_OPTIONAL, 'Comma delimited list of language files that should be skipped (e.g. "addresses,default").', null);

		$this->addArgument('languages', InputArgument::OPTIONAL, 'Languages to process as comma delimited list or "all" for all languages.', 'all');
	}

	protected function write(OutputInterface $output, $messages, $newline = false, $type = 0, $verbosity = OutputInterface::VERBOSITY_NORMAL)
	{
		if ($output->getVerbosity() >= $verbosity)
		{
			$output->write($messages, $newline, $type);
		}
	}

	protected function writeVerbose(OutputInterface $output, $messages, $newline = false, $type = 0)
	{
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE)
		{
			$output->write($messages, $newline, $type);
		}
	}

	protected function writeAlways(OutputInterface $output, $messages, $newline = false, $type = 0)
	{
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_QUIET)
		{
			$output->write($messages, $newline, $type);
		}
	}

	protected function writeln(OutputInterface $output, $messages, $type = 0, $verbosity = OutputInterface::VERBOSITY_NORMAL)
	{
		if ($output->getVerbosity() >= $verbosity)
		{
			$output->writeln($messages, $type);
		}
	}

	protected function writelnVerbose(OutputInterface $output, $messages, $type = 0)
	{
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE)
		{
			$output->writeln($messages, $type);
		}
	}

	protected function writelnAlways(OutputInterface $output, $messages, $type = 0)
	{
		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_QUIET)
		{
			$output->writeln($messages, $type);
		}
	}

	/**
	 * Fetch some value from the config.
	 * First the value will be read from composer.json (section /extra/contao/...) and then from the global ctb config
	 * (if any exists).
	 *
	 * @param string $name the config value to retrieve.
	 *
	 * @return mixed
	 */
	protected function getConfigValue($name)
	{
		if (substr($name, 0, 1) != '/')
		{
			$name = '/' .$name;
		}
		$config = new JsonConfig(getcwd() . '/composer.json');
		$value = $config->getConfigValue('/extra/contao' . $name);

		// fallback to global config.
		if ($value === null)
		{
			/** @var JsonConfig $config */
			$config = $this->getApplication()->getConfig();
			$value = $config->getConfigValue($name);
		}

		return $value;
	}

	protected function checkValidSlug($slug)
	{
		if (preg_match_all('#^([a-z,A-Z,0-9,\-,_]*)(.+)?$#', $slug, $matches)
			&& (strlen($matches[2][0]) > 0))
		{
			throw new \RuntimeException(sprintf(
				'Error: prefix "%s" is invalid. It must only contain letters, numbers, underscores and hyphens. Found problem near: "%s"',
				$slug,
				$matches[2][0]
			));
		}
	}

	abstract protected function getLanguageBasePath();

	protected function determineLanguages(OutputInterface $output, $srcdir, $filter = array())
	{
		if (!is_dir($srcdir))
		{
			throw new \InvalidArgumentException(sprintf('The path %s does not exist.', $srcdir));
		}

		$this->writelnVerbose($output, sprintf('<info>scanning for languages in: %s</info>', $srcdir));
		$matches = array();
		$iterator = new \DirectoryIterator($srcdir);
		do
		{
			$item = $iterator->getFilename();

			if ((!$iterator->isDot()) && (strlen($item) == 2) && ((!$filter) || in_array($item, $filter)))
			{
				$matches[] = $item;
				$this->writelnVerbose($output, sprintf('<info>using: %s</info>', $item));
			}
			elseif(!$iterator->isDot())
			{
				$this->writelnVerbose($output, sprintf('<info>not using: %s</info>', $item));
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
		$this->skipFiles    = explode(',', $input->getOption('skip-files'));

		$this->checkValidSlug($this->project);
		$this->checkValidSlug($this->prefix);

		if (!$this->project)
		{
			$this->project = $this->getConfigValue('/transifex/project');
			if (!$this->project)
			{
				throw new \RuntimeException('Error: unable to determine transifex project name.');
			}

			$this->writelnVerbose($output, sprintf('<info>automatically using project: %s</info>', $this->project));
		}

		if (!$this->prefix)
		{
			$this->prefix = $this->getConfigValue('/transifex/prefix');

			if (!$this->prefix)
			{
				throw new \RuntimeException('Error: unable to determine transifex prefix.');
			}
			$this->writelnVerbose($output, sprintf('<info>automatically using prefix: %s</info>', $this->prefix));
		}

		if (!$this->txlang)
		{
			$this->txlang = $this->getConfigValue('/transifex/languages_tx');

			if (!$this->txlang)
			{
				throw new \RuntimeException('Error: unable to determine transifex root folder.');
			}
			$this->writelnVerbose($output, sprintf('<info>automatically using xliff folder: %s</info>', $this->txlang));
		}

		if (!$this->ctolang)
		{
			$this->ctolang = $this->getConfigValue('/transifex/languages_cto');

			if (!$this->ctolang)
			{
				throw new \RuntimeException('Error: unable to determine contao language root folder.');
			}
			$this->writelnVerbose($output, sprintf('<info>automatically using Contao language folder: %s</info>', $this->ctolang));
		}

        if (!$this->skipFiles) {
            $this->skipFiles = $this->getConfigValue('/transifex/skip_files');
        } else {
            // Make sure it is an array
            $this->skipFiles = array();
        }

		$activeLanguages = array();
		if (($langs = $input->getArgument('languages')) != 'all')
		{
			$activeLanguages = explode(',', $langs);
		}

		$this->determineLanguages($output, $this->getLanguageBasePath(), $activeLanguages);
	}

}