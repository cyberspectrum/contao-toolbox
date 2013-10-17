<?php

namespace CyberSpectrum\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use CyberSpectrum\Translation\Xliff;
use CyberSpectrum\Translation\Contao;

class ConvertFromXliff extends ConvertBase
{
	protected function configure()
	{
		parent::configure();

		$this->setName('from-xliff');
		$this->setDescription('Update Contao language files from xliff translations.');

		$this->setHelp('Convert the xliff files from the set transifex folder into the contao folder.' . PHP_EOL);
	}

	protected function getLanguageBasePath()
	{
		return $this->txlang;
	}

	protected function getDestinationBasePath()
	{
		return $this->ctolang;
	}

	protected function isValidSourceFile($file)
	{
		return (substr($file, -4) == '.xlf');
	}

	protected function isValidDestinationFile($file)
	{
		return (substr($file, -4) == '.php');
	}

	protected function convert(OutputInterface $output, Xliff\File $src, Contao\File $dst)
	{
		$changed = false;

		foreach ($src->getKeys() as $key)
		{
			if (($value = $src->getTarget($key)) !== null)
			{
				if ($dst->getValue($key) != $value)
				{
					$changed = true;
					$dst->setValue($key, $value);
				}
			}
			else
			{
				if ($dst->getValue($key) !== null)
				{
					$changed = true;
					$dst->removeValue($key);
				}
			}
		}

		return $changed;
	}

	protected function processLanguage(OutputInterface $output, $language)
	{
		$this->writeln($output, sprintf('processing language: <info>%s</info>...', $language));

		$destinationFiles = array();
		foreach ($this->baseFiles as $file)
		{
			$this->writelnVerbose($output, sprintf('processing file: <info>%s</info>...', $file));

			$srcFile            = $this->getLanguageBasePath() .DIRECTORY_SEPARATOR . $language .DIRECTORY_SEPARATOR . $file;

			// not a file from transifex received yet.
			if (!file_exists($srcFile))
			{
				continue;
			}

			$src                = new Xliff\File($srcFile);

			$domain             = $src->getOriginal();

			if ($domain != basename($file, '.xlf'))
			{
				throw new \InvalidArgumentException(sprintf('Unexpected domain "%s" found in file "%s" instead of domain "%s"', $domain, $srcFile, basename($file, '.xlf')));
			}

			$dstFile            = $domain . '.php';
			$destinationFiles[] = $dstFile;

			$dstDir = $this->getDestinationBasePath() .DIRECTORY_SEPARATOR . $language;
			if (!is_dir($dstDir))
			{
				mkdir($dstDir, 0755, true);
			}

			$dest = new Contao\File($dstDir . DIRECTORY_SEPARATOR . $dstFile);

			$changed = $this->convert($output, $src, $dest);

			if ($changed)
			{
				$dest->setLanguage($language);
				$dest->setTransifexProject($this->project);
				$dest->setLastChange($src->getDate());

				if ($dest->getKeys())
				{
					$dest->save();
				}
				else
				{
					unlink($dstDir . DIRECTORY_SEPARATOR . $dstFile);
				}
			}
		}

		if ($this->cleanup && ($files = array_diff($this->determinePresentFiles($language), $destinationFiles)))
		{
			$this->writeln($output, sprintf('the following obsolete files have been found and will get deleted: <info>%s</info>', implode(', ', $files)));

			foreach ($files as $file)
			{
				unlink($this->getDestinationBasePath() .DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $file);
				$this->writelnVerbose($output, sprintf('deleting obsolete file <info>%s</info>', $file));
			}
		}
	}
}