<?php


namespace CyberSpectrum\Transifex;

use Buzz\Browser;
use Buzz\Listener\BasicAuthListener;
use Buzz\Message\Request;
use Buzz\Message\Response;

class Transport
{
	protected $browser;

	public function __construct($user, $pass)
	{
		$this->browser = new Browser();
		$this->browser->setListener(new BasicAuthListener($user, $pass));
	}


	protected function checkError(Response $response)
	{
		if (($response->getStatusCode() != 200) && ($response->getStatusCode() != 201))
		{
			switch ($response->getHeader('Content-Type'))
			{
				case 'text/plain':
					throw new \RuntimeException('Error: ' . $response->getContent() . ' URI: ' . $this->browser->getLastRequest()->getResource());
					break;
				case 'application/json':
						$error = json_decode($response->getContent());
						if (isset($error->message))
						{
							throw new \RuntimeException($error->message);
						}
					break;
				default:
					throw new \RuntimeException('Unknown Error: No error message was returned from the server - Code: ' . $response->getStatusCode() . ' URI: ' . $this->browser->getLastRequest()->getResource());
			}
		}
	}

	public function POST($command, $params=null, $postcontenttype = 'application/json')
	{
		$url = 'http://www.transifex.com/api/2/' . $command;

		$headers = array('Content-type: ' . $postcontenttype);

		$content = $params;
		if ($postcontenttype == 'application/json')
		{
			$content = json_encode($params);
		}

		/** @var  $response */
		$response = $this->browser->post($url, $headers, $content);

		$this->checkError($response);

		return $response->getContent();
	}

	public function PUT($command, $params=null, $postcontenttype = 'application/json')
	{
		$url = 'http://www.transifex.com/api/2/' . $command;

		$headers = array('Content-type: ' . $postcontenttype);

		$content = $params;
		if ($postcontenttype == 'application/json')
		{
			$content = json_encode($params);
		}

		/** @var  $response */
		$response = $this->browser->put($url, $headers, $content);

		$this->checkError($response);

		return $response->getContent();
	}


	public function execute($command, $params=null)
	{
		$url = 'http://www.transifex.com/api/2/' . $command;

		if (substr($url, -1) !== '/')
		{
			$url .= '/';
		}

		if ($params)
		{
			$p=array();
			foreach ($params as $k=>$v)
			{
				$p[] = urlencode($k).'='.urlencode($v);
			}
			$url .= '?' . implode('&', $p);
		}

		/** @var  $response */
		$response = $this->browser->get($url);

		$this->checkError($response);

		return $response->getContent();
	}

	public function executeJson($command, $params=null, $postdata=null)
	{
		return json_decode($this->execute($command, $params, $postdata), true);
	}

}