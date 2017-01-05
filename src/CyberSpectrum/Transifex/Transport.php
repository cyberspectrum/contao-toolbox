<?php

/**
 * This file is part of cyberspectrum/contao-toolbox.
 *
 * (c) 2013-2017 CyberSpectrum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    cyberspectrum/contao-toolbox.
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace CyberSpectrum\Transifex;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

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
        $this->client = new Client(
            [
                'base_uri' => 'http://www.transifex.com/api/2/',
                'headers'  => ['Authorization' => 'Basic ' . base64_encode($user . ':' . $pass)]
            ]
        );
    }

    /**
     * Check the response for an error.
     *
     * @param ResponseInterface $response The response received.
     *
     * @param string            $url      The request url.
     *
     * @return void
     *
     * @throws \RuntimeException On any error.
     */
    private function checkError(ResponseInterface $response, $url)
    {
        if (($response->getStatusCode() == 200) || ($response->getStatusCode() == 201)) {
            return;
        }

        switch ($response->getHeader('Content-Type')) {
            case 'text/plain':
                throw new \RuntimeException('Error: ' . $response->getBody() . ' URI: ' . $url);
            case 'application/json':
                $error = json_decode($response->getBody());
                if (isset($error->message)) {
                    throw new \RuntimeException($error->message . ' URI: ' . $url);
                }
                break;
            default:
                throw new \RuntimeException(
                    'Unknown Error: No error message was returned from the server - Code: ' . $response->getStatusCode(
                    ) . ' URI: ' . $url
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
     * @return StreamInterface|null|string
     */
    public function post($command, $params = null, $postcontenttype = 'application/json')
    {
        $url = $command;

        $headers = array('Content-Type' => $postcontenttype);

        $content = $params;
        if ($postcontenttype == 'application/json') {
            $content = json_encode($params);
        }

        try {
            $response = $this->client->post($url, ['headers' => $headers, 'body' => $content]);
        } catch (\Exception $e) {
            return null;
        }
        $this->checkError($response, $url);

        return $response->getBody();
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
     * @return StreamInterface|null|string
     */
    public function put($command, $params = null, $postcontenttype = 'application/json')
    {
        $url = $command;

        $headers = array('Content-Type' => $postcontenttype);

        $content = $params;
        if ($postcontenttype == 'application/json') {
            $content = json_encode($params);
        }

        try {
            $response = $this->client->put($url, ['headers' => $headers, 'body' => $content]);
        } catch (\Exception $e) {
            return null;
        }
        $this->checkError($response, $url);

        return $response->getBody();
    }

    /**
     * Execute a command on the API.
     *
     * @param string        $command The command to post.
     *
     * @param null|string[] $params  The parameters (if any).
     *
     * @return StreamInterface|null|string
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

        try {
            $response = $this->client->get($url);
        } catch (\Exception $e) {
            return null;
        }
        $this->checkError($response, $url);

        return $response->getBody();
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
