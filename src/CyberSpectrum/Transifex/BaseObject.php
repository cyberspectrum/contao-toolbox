<?php

namespace CyberSpectrum\Transifex;

class BaseObject
{
	protected $transport;


	public function __construct(Transport $transport)
	{
		$this->transport = $transport;
	}

	protected function getTransport()
	{
		return $this->transport;
	}

	protected function ensureParameter($name)
	{
		if (!$this->$name)
		{
			throw new \InvalidArgumentException(get_class($this) . ' is missing parameter: ' . $name);
		}

		return $this->$name;
	}

	protected function POST($command, $params=null, $postcontenttype = 'application/json')
	{
		return $this->transport->POST($command, $params, $postcontenttype);
	}

	protected function PUT($command, $params=null, $postcontenttype = 'application/json')
	{
		return $this->transport->PUT($command, $params, $postcontenttype);
	}

	protected function execute($command, $params=null, $postdata=null)
	{
		return $this->transport->execute($command, $params, $postdata);
	}

	protected function executeJson($command, $params=null, $postdata=null)
	{
		return $this->transport->executeJson($command, $params, $postdata);
	}
}