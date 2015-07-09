<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Transifex;

use Guzzle\Http\Client;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * This class abstracts the HTTP transport.
 */
class Transport
{
    /**
     * The guzzle http client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Create a new instance.
     *
     * @param string $user The transifex API user.
     *
     * @param string $pass The transifex API password.
     */
    public function __construct($user, $pass)
    {
        $this->client = new Client('http://www.transifex.com/api/2/');
        $this->client->getConfig()->setPath('request.options/auth', array($user, $pass, 'Basic|Digest'));
    }

    /**
     * Check the response for an error.
     *
     * @param Response         $response The response received.
     *
     * @param RequestInterface $request  The request sent.
     *
     * @return void
     *
     * @throws \RuntimeException On any error.
     */
    protected function checkError(Response $response, RequestInterface $request)
    {
        if (($response->getStatusCode() == 200) || ($response->getStatusCode() == 201)) {
            return;
        }

        switch ($response->getHeader('Content-Type')) {
            case 'text/plain':
                throw new \RuntimeException('Error: ' . $response->getBody(true) . ' URI: ' . $request->getUrl());
            case 'application/json':
                $error = json_decode($response->getBody(true));
                if (isset($error->message)) {
                    throw new \RuntimeException($error->message . ' URI: ' . $request->getUrl());
                }
                break;
            default:
                throw new \RuntimeException(
                    'Unknown Error: No error message was returned from the server - Code: ' . $response->getStatusCode(
                    ) . ' URI: ' . $response->getRequest()->getUrl()
                );
        }
    }

    /**
     * Perform a post request.
     *
     * @param string     $command         The command to execute.
     *
     * @param null|array $params          The parameters to use.
     *
     * @param string     $postcontenttype The content type of the POST data.
     *
     * @return EntityBodyInterface|null|string
     */
    public function post($command, $params = null, $postcontenttype = 'application/json')
    {
        $url = $command;

        $headers = array('Content-Type' => $postcontenttype);

        $content = $params;
        if ($postcontenttype == 'application/json') {
            $content = json_encode($params);
        }

        $request = $this->client->post($url, $headers, $content);
        try {
            $response = $request->send();
            $this->checkError($response, $request);
        } catch (\Exception $e) {
            $this->checkError($request->getResponse(), $request);

            return null;
        }

        return $response->getBody(true);
    }

    /**
     * Perform a put request.
     *
     * @param string     $command         The command to execute.
     *
     * @param null|array $params          The parameters to use.
     *
     * @param string     $postcontenttype The content type of the POST data.
     *
     * @return EntityBodyInterface|null|string
     */
    public function put($command, $params = null, $postcontenttype = 'application/json')
    {
        $url = $command;

        $headers = array('Content-Type' => $postcontenttype);

        $content = $params;
        if ($postcontenttype == 'application/json') {
            $content = json_encode($params);
        }

        $request = $this->client->put($url, $headers, $content);
        try {
            $response = $request->send();
            $this->checkError($response, $request);
        } catch (\Exception $e) {
            $this->checkError($request->getResponse(), $request);

            return null;
        }

        return $response->getBody(true);
    }

    /**
     * Execute a command on the API.
     *
     * @param string        $command The command to post.
     *
     * @param null|string[] $params  The parameters (if any).
     *
     * @return EntityBodyInterface|null|string
     */
    public function execute($command, $params = null)
    {
        $url = $command;

        if (substr($url, -1) !== '/') {
            $url .= '/';
        }

        if ($params) {
            $parameters = array();
            foreach ($params as $k => $v) {
                if (strlen($v)) {
                    $parameters[] = urlencode($k) . '=' . urlencode($v);
                } else {
                    $parameters[] = urlencode($k);
                }
            }
            $url .= '?' . implode('&', $parameters);
        }

        $request = $this->client->get($url);
        try {
            $response = $request->send();
            $this->checkError($response, $request);
        } catch (\Exception $e) {
            $this->checkError($request->getResponse(), $request);

            return null;
        }

        return $response->getBody(true);
    }

    /**
     * Execute a command on the API and return the content as json decoded array..
     *
     * @param string        $command The command to post.
     *
     * @param null|string[] $params  The parameters (if any).
     *
     * @return array
     */
    public function executeJson($command, $params = null)
    {
        return json_decode($this->execute($command, $params), true);
    }
}
