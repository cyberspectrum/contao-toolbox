<?php

namespace CyberSpectrum\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

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

	protected function convert(Xliff\File $src, Contao\File $dst)
	{
		foreach ($src->getKeys() as $key)
		{
			if (($value = $src->getTarget($key)) !== null)
			{
				$dst->setValue($key, $value);
			}
			else
			{
				$dst->removeValue($key);
			}
		}
	}

	protected function processLanguage($language)
	{
		$this->output->writeln(sprintf('processing language: %s...', $language));
		$destinationFiles = array();
		foreach ($this->baseFiles as $file)
		{
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
			$dest->setLanguage($language);
			$dest->setTransifexProject($this->project);
			$dest->setLastChange($src->getDate());

			$this->convert($src, $dest);
			$dest->save();

			$this->output->writeln(sprintf('processing file: %s...', $file));
		}

		if ($this->cleanup && ($files = array_diff($this->determinePresentFiles($language), $destinationFiles)))
		{
			$this->output->writeln(sprintf('the following obsolete files have been found: %s', implode(', ', $files)));
			foreach ($files as $file)
			{
				unlink($this->getDestinationBasePath() .DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $file);
				$this->output->writeln('deleting ' . $language . DIRECTORY_SEPARATOR . $file);
			}
		}
	}
}