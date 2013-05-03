<?php

namespace CyberSpectrum\Command;

use CyberSpectrum\Transifex\Project;
use CyberSpectrum\Transifex\Resource;
use CyberSpectrum\Translation\Xliff\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadTransifex extends TransifexBase
{
	protected function configure()
	{
		parent::configure();
		$this->setName('download-transifex');
		$this->setDescription('Download xliff translations from transifex.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!($this->project && $this->getApi()))
		{
			$output->writeln('No project set or no API received, exiting.');
			return;
		}

		$project = new Project($this->getApi());

		$project->setSlug($this->project);

		$resources = $project->getResources();

		foreach ($resources as $resource)
		{
			/** @var \CyberSpectrum\Transifex\Resource $resource */
			if (substr($resource->getSlug(), 0, strlen($this->prefix)) != $this->prefix)
			{
				$output->writeln('file ' . $resource->getSlug() . ' is not for this repository, ignored.');
				continue;
			}
			$output->writeln('polling ' . $resource->getSlug());
			$resource->fetchDetails();

			$allLanguages = ($input->getArgument('languages') == 'all');
			foreach ($resource->getAvailableLanguages() as $code => $language)
			{
				// we are using 2char iso 639-1 in Contao - what a pitty :(
				if (($allLanguages || in_array(substr($code, 0, 2), $this->languages)) && ($code != $this->baselanguage))
				{
					$output->writeln('updating language ' . $code);
					// pull it.
					$data = $resource->fetchTranslation($code);
					if ($data)
					{
						$domain = substr($resource->getSlug(), strlen($this->prefix));
						$localfile = $this->txlang. DIRECTORY_SEPARATOR . substr($code, 0, 2) . DIRECTORY_SEPARATOR . $domain . '.xlf';
						$local = new File($localfile);
						if (!file_exists($localfile))
						{
							// set base values.
							$local->setDatatype('php');
							$local->setOriginal($domain);
							$local->setSrclang($this->baselanguage);
							$local->setTgtlang(substr($code, 0, 2));
						}

						$new = new File(null);
						$new->loadXML($data);

						foreach ($new->getKeys() as $key)
						{
							if ($value = $new->getSource($key))
							{
								$local->setSource($key, $value);
								if ($value = $new->getTarget($key))
								{
									$local->setTarget($key, $value);
								}
							}
						}
						foreach (array_diff($new->getKeys(), $local->getKeys()) as $key)
						{
							$output->writeln($key . ' seems to be obsolete.');
						}

						if ($local->getKeys())
						{
							if (!is_dir(dirname($localfile)))
							{
								mkdir(dirname($localfile), 0755, true);
							}
							$local->save();
						}
					}
					die();
				}
				else
				{
					$output->writeln('skipping language ' . $code);
				}
			}
		}
	}
}