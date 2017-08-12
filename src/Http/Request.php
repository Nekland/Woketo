<?php

/**
 * This file is a part of a nekland library
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Http;

use Nekland\Woketo\Exception\Http\HttpException;
use Nekland\Woketo\Meta;

/**
 * Class Request
 *
 * @internal
 */
class Request extends AbstractHttpMessage
{
    const HTTP_1_1 = '1.1';
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    private function __construct() {}

    /**
     * @return Request
     */
    private function setMethod($method) : Request
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $uri
     * @return Request
     */
    private function setUri(string $uri) : Request
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getUri() : string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
    }

    /**
     * @return int
     */
    public function getVersion() : int
    {
        return (int) $this->getHeaders()->get('Sec-WebSocket-Version');
    }

    /**
     * @param int $version
     * @return Request
     */
    public function setVersion(int $version) : Request
    {
        $this->addHeader('Sec-WebSocket-Version', $version);

        return $this;
    }

    /**
     * @param string $key
     * @return Request
     */
    public function setKey(string $key) : Request
    {
        $this->addHeader('Sec-WebSocket-Key', $key);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKey()
    {
        return $this->getHeader('Sec-WebSocket-Key');
    }

    /**
     * @param string $host
     * @return self
     */
    private function setHost(string $host) : Request
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param int $port
     * @return Request
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtensions() : array
    {
        $originalHeaders = $this->getHeaders()->get('Sec-WebSocket-Extensions');
        if (!\is_array($originalHeaders)) {
            $originalHeaders = [$originalHeaders];
        }

        $extensionHeaders = [];
        $extensions = [];

        foreach ($originalHeaders as $extensionHeader) {
            $extensionHeaders = \array_merge($extensionHeaders, \array_map('trim', \explode(',', $extensionHeader)));
        }

        foreach ($extensionHeaders as $extension) {
            $explodingHeader = \explode(';', $extension);
            $extensionName = \trim($explodingHeader[0]);
            $extensions[$extensionName] = [];

            if (\count($explodingHeader)) {
                unset($explodingHeader[0]); // removing the name of the extension
                foreach($explodingHeader as $variable) {
                    $explodeVariable = \explode('=', $variable);

                    // The value can be with or without quote. We need to remove extra quotes.
                    $value = \preg_replace('/^"(.+)"$/', '$1', trim($explodeVariable[1]));
                    $value = \str_replace('\\"', '"', $value);

                    $extensions[$extensionName][\trim($explodeVariable[0])] = $value;
                }
            }
        }

        return $extensions;
    }

    /**
     * @return string
     */
    public function getRequestAsString() : string
    {
        $request = mb_strtoupper($this->method) . ' ' . $this->uri . " HTTP/1.1\r\n";
        $request .= 'Host: ' . $this->host . ($this->port ? ':' . $this->port : '') . "\r\n";
        $request .= 'User-Agent: Woketo/' . Meta::VERSION . "\r\n";
        $request .= "Upgrade: websocket\r\n";
        $request .= "Connection: Upgrade\r\n";

        foreach ($this->getHeaders() as $key => $header) {
            $request .= $key . ': ' . $header . "\r\n";
        }

        $request .= "\r\n";

        return $request;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getRequestAsString();
    }

    /**
     * @param string $requestString
     * @return Request
     * @throws HttpException
     */
    public static function create(string $requestString)
    {
        $request = new Request;

        $lines = \explode("\r\n", $requestString);
        Request::initRequest($lines[0], $request);

        unset($lines[0]);
        Request::initHeaders($lines, $request);

        if (empty($request->getHeaders()->get('Sec-WebSocket-Key')) || empty($request->getHeaders()->get('Upgrade')) || \strtolower($request->getHeaders()->get('Upgrade')) !== 'websocket') {
            throw new HttpException(sprintf("The request is not a websocket upgrade request, received:\n%s", $requestString));
        }

        return $request;
    }

    /**
     * @param string   $uri
     * @param string   $host
     * @param int|null $port
     * @return Request
     */
    public static function createClientRequest(string $uri, string $host, int $port = null)
    {
        $request = new Request();

        $request
            ->setMethod('GET')
            ->setUri($uri)
            ->setHttpVersion(Request::HTTP_1_1)
            ->setHost($host)
        ;

        if ($port) {
            $request->setPort($port);
        }

        return $request;
    }

    /**
     * @param string  $firstLine
     * @param Request $request
     * @throws HttpException
     */
    protected static function initRequest(string $firstLine, Request $request)
    {
        $httpElements = \explode(' ', $firstLine);

        if (\count($httpElements) < 3) {
            throw Request::createNotHttpException($firstLine);
        }

        $httpElements[2] = \trim($httpElements[2]);
        if (!\preg_match('/HTTP\/.+/', $httpElements[2])) {
            throw Request::createNotHttpException($firstLine);
        }
        $request->setHttpVersion($httpElements[2]);

        if (!\in_array($httpElements[0], ['POST', 'GET', 'PUT', 'DELETE'])) {
            throw new HttpException(
                \sprintf('Request not supported, only POST, GET, PUT, and DELETE are supported. "%s" received.', $httpElements[0])
            );
        }

        $request->setMethod($httpElements[0]);
        $request->setUri($httpElements[1]);
    }
}
