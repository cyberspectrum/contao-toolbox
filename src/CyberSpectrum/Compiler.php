<?php

namespace CyberSpectrum;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * The Compiler class compiles a phar
 */
class Compiler
{
	/**
	 * Compiles composer into a single phar file
	 *
	 * Based upon Compiler from composer.
	 *
	 * @throws \RuntimeException
	 * @param  string            $pharFile The full path to the file to create
	 */
	public function compile($pharFile = 'ctb.phar')
	{
		if (file_exists($pharFile)) {
			unlink($pharFile);
		}

		$process = new Process('git log --pretty="%H" -n1 HEAD', __DIR__);
		if ($process->run() != 0) {
			throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from git repository clone and that git binary is available.');
		}
		$this->version = trim($process->getOutput());

		$process = new Process('git describe --tags HEAD');
		if ($process->run() == 0) {
			$this->version = trim($process->getOutput());
		}

		$phar = new \Phar($pharFile, 0, 'ctb.phar');
		$phar->setSignatureAlgorithm(\Phar::SHA1);

		$phar->startBuffering();

		$finder = new Finder();
		$finder->files()
			->ignoreVCS(true)
			->name('*.php')
			->notName('Compiler.php')
			->notName('ClassLoader.php')
			->in(__DIR__.'/..')
		;

		foreach ($finder as $file) {
			$this->addFile($phar, $file);
		}
		$this->addFile($phar, new \SplFileInfo(__DIR__ . '/../../vendor/composer/ClassLoader.php'), false);

		$finder = new Finder();
		$finder->files()
			->ignoreVCS(true)
			->name('*.php')
			->exclude('Tests')
			->in(__DIR__.'/../../vendor/symfony/')
			->in(__DIR__.'/../../vendor/guzzle/')
		;

		foreach ($finder as $file) {
			$this->addFile($phar, $file);
		}

		$this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/autoload.php'));
		$this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_psr4.php'));
		$this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_namespaces.php'));
		$this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_classmap.php'));
		$this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/autoload_real.php'));
		if (file_exists(__DIR__.'/../../vendor/composer/include_paths.php')) {
			$this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/include_paths.php'));
		}
		$this->addFile($phar, new \SplFileInfo(__DIR__.'/../../vendor/composer/ClassLoader.php'));
		$this->addBinFile($phar);

		// Stubs
		$phar->setStub($this->getStub());

		$phar->stopBuffering();

		// disabled for interoperability with systems without gzip ext
		// $phar->compressFiles(\Phar::GZ);

		unset($phar);
	}

	private function addFile($phar, $file, $strip = true)
	{
		$path = str_replace(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR, '', $file->getRealPath());

		$content = file_get_contents($file);
		if ($strip) {
			$content = $this->stripWhitespace($content);
		} elseif ('LICENSE' === basename($file)) {
			$content = "\n".$content."\n";
		}

		$content = str_replace('@package_version@', $this->version, $content);

		$phar->addFromString($path, $content);
	}

	private function addBinFile($phar)
	{
		$content = file_get_contents(__DIR__.'/../../bin/ctb');
		$content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
		$phar->addFromString('bin/ctb', $content);
	}

	/**
	 * Removes whitespace from a PHP source string while preserving line numbers.
	 *
	 * @param  string $source A PHP string
	 * @return string The PHP string with the whitespace removed
	 */
	private function stripWhitespace($source)
	{
		if (!function_exists('token_get_all')) {
			return $source;
		}

		$output = '';
		foreach (token_get_all($source) as $token) {
			if (is_string($token)) {
				$output .= $token;
			} elseif (in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
				$output .= str_repeat("\n", substr_count($token[1], "\n"));
			} elseif (T_WHITESPACE === $token[0]) {
				// reduce wide spaces
				$whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
				// normalize newlines to \n
				$whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
				// trim leading spaces
				$whitespace = preg_replace('{\n +}', "\n", $whitespace);
				$output .= $whitespace;
			} else {
				$output .= $token[1];
			}
		}

		return $output;
	}

	private function getStub()
	{
		$stub = <<<'EOF'
#!/usr/bin/env php
<?php
/*
 * This file is part of ctb.
 *
 * For the full copyright and license information, please view
 * the license that is located at the bottom of this file.
 */

Phar::mapPhar('ctb.phar');

EOF;

		// add warning once the phar is older than 30 days
		if (preg_match('{^[a-f0-9]+$}', $this->version)) {
			$warningTime = time() + 30*86400;
			$stub .= "define('COMPOSER_DEV_WARNING_TIME', $warningTime);\n";
		}

		return $stub . <<<'EOF'
require 'phar://ctb.phar/bin/ctb';

__HALT_COMPILER();
EOF;
	}
}
