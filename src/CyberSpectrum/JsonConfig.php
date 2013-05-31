<?php

namespace CyberSpectrum;

class JsonConfig
{
	protected $data;

	public function __construct($filename)
	{
		if (!file_exists($filename))
		{
			throw new \InvalidArgumentException('Error: file not found: ' . $filename);
		}

		$this->data = json_decode(file_get_contents($filename), true);

		if ($this->data === null)
		{
			throw new \InvalidArgumentException(sprintf('Error parsing %s - %s', $filename, json_last_error()));
		}
	}

	protected function scanTo($path, $scope)
	{
		if (!($sub = array_shift($path)) || !isset($scope[$sub]))
		{
			return null;
		}

		if (!$path)
		{
			return $scope[$sub];
		}

		return $this->scanTo($path, $scope[$sub]);
	}

	public function getConfigValue($path)
	{
		$chunks = explode('/', $path);

		if (!$chunks[0])
		{
			array_shift($chunks);
		}

		return $this->scanTo($chunks, $this->data);
	}

	public function getTransifexPrefix()
	{
		return $this->getConfigValue('/extra/contao/transifex/prefix');
	}
}
