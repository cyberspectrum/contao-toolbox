<?php

namespace CyberSpectrum\Command;

use CyberSpectrum\Transifex\Transport;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TransifexBase extends CommandBase
{
	protected $api;

	protected function getApi()
	{
		return $this->api;
	}

	protected function configure()
	{
		parent::configure();

		$this->addOption('user', 'U', InputOption::VALUE_OPTIONAL, 'Username on transifex, if empty prompt on console.', null);
		$this->addOption('pass', 'P', InputOption::VALUE_OPTIONAL, 'Password on transifex, if empty prompt on console.', null);

		$this->setHelp(
			'NOTE: you can also specify username and password via the environment for automated jobs.' . PHP_EOL .
			'user: transifexuser=username' . PHP_EOL .
			'pass: transifexpass=password' . PHP_EOL
		);
	}

	protected function getLanguageBasePath()
	{
		return realpath($this->txlang);
	}

	protected function getAllTxFiles($language)
	{
		$iterator = new \DirectoryIterator($this->txlang. DIRECTORY_SEPARATOR . $language);

		$files = array();
		while ($iterator->valid())
		{
			if (!$iterator->isDot() && $iterator->isFile() && $iterator->getExtension() == 'xlf')
			{
				$files[$iterator->getPathname()] = $iterator->getFilename();
			}
			$iterator->next();
		}

		return $files;
	}

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);

		$user = $input->getOption('user');
		if (!$user)
		{
			if ($user = getenv('transifexuser'))
			{
				$output->writeln('using user specified in environment.');
			}
			elseif ($input->isInteractive())
			{
				/** @var \Symfony\Component\Console\Helper\DialogHelper $dialog */
				$dialog = $this->getHelperSet()->get('dialog');

				if (!($user = $dialog->ask($output, 'User:')))
				{
					$output->writeln('ok... no user? no work!');
					return;
				}
			}
			else
			{
				throw new \RuntimeException('Error: you must either specify an username on the commandline or run interactive.');
			}
		}

		$pass = $input->getOption('pass');

		if (!$pass)
		{
			if ($pass = getenv('transifexpass'))
			{
				$output->writeln('using password specified in environment.');
			}
			elseif ($input->isInteractive())
			{
				/** @var \Symfony\Component\Console\Helper\DialogHelper $dialog */
				$dialog = $this->getHelperSet()->get('dialog');

				if (!($pass = $dialog->askHiddenResponse($output, 'Password:')))
				{
					$output->writeln('ehm... no password? I don\'t believe you!');
					return;
				}
			}
			else
			{
				throw new \RuntimeException('Error: you must either specify an username on the commandline or run interactive.');
			}
		}

		if ($user && $pass)
		{
			$this->api = new Transport($user, $pass);
		}
	}
}