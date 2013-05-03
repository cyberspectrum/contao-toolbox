<?php

namespace CyberSpectrum\Command;

use CyberSpectrum\Translation\Xliff;
use CyberSpectrum\Translation\Contao;

class ConvertToXliff extends ConvertBase
{
	protected function configure()
	{
		parent::configure();

		$this->setName('to-xliff');
		$this->setDescription('Update xliff translations from Contao base language.');

		$this->setHelp('Convert the base language from the contao folder into files in transifex folder' . PHP_EOL);
	}

	protected function getLanguageBasePath()
	{
		return $this->ctolang;
	}

	protected function getDestinationBasePath()
	{
		return $this->txlang;
	}

	protected function isValidSourceFile($file)
	{
		return (substr($file, -4) == '.php');
	}

	protected function isValidDestinationFile($file)
	{
		return (substr($file, -4) == '.xlf');
	}

	protected function convert(Contao\File $src, Xliff\File $dst, Contao\File $base)
	{
		$baseKeys = $base->getKeys();
		foreach ($baseKeys as $key)
		{
			if (!($basVal = $base->getValue($key)))
			{
				$dst->remove($key);
				continue;
			}
			$dst->setSource($key, $basVal);
			if (($value = $src->getValue($key)) !== null)
			{
				$dst->setTarget($key, $value);
			}
		}

		foreach ($dst->getKeys() as $key)
		{
			if (!in_array($key, $baseKeys))
			{
				$this->output->writeln(sprintf('UNDEFINED LANGUAGE KEY FOUND: %s...', $key));
				$dst->remove($key);
			}
		}
	}

	protected function processLanguage($language)
	{
		$this->output->writeln(sprintf('processing language: %s...', $language));
		$destinationFiles = array();
		foreach ($this->baseFiles as $file)
		{
			$this->output->writeln(sprintf('processing file: %s...', $file));

			$basFile            = $this->getLanguageBasePath() .DIRECTORY_SEPARATOR . $this->baselanguage .DIRECTORY_SEPARATOR . $file;
			$srcFile            = $this->getLanguageBasePath() .DIRECTORY_SEPARATOR . $language .DIRECTORY_SEPARATOR . $file;

			$domain             = basename($file, '.php');

			$dstFile            = $domain . '.xlf';
			$destinationFiles[] = $dstFile;

			$src                = new Contao\File($srcFile);
			$base               = new Contao\File($basFile);

			$dstDir = $this->getDestinationBasePath() .DIRECTORY_SEPARATOR . $language;
			if (!is_dir($dstDir))
			{
				mkdir($dstDir, 0755, true);
			}

			$dest = new Xliff\File($dstDir . DIRECTORY_SEPARATOR . $dstFile);
			$dest->setDatatype('php');
			$dest->setSrclang($this->baselanguage);
			$dest->setTgtlang($language);
			$dest->setOriginal($domain);
			if (file_exists($srcFile))
			{
				$time = filemtime($srcFile);
			}
			else
			{
				$time = filemtime($basFile);
			}
			$dest->setDate($time);

			$this->convert($src, $dest, $base);
			$dest->save();
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