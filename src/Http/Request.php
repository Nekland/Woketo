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

use Nekland\Woketo\Exception\HttpException;

class Request extends AbstractHttpMessage
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $uri;

    private function __construct() {}

    /**
     * @return Request
     */
    private function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $uri
     * @return Request
     */
    private function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getMethod()
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
     * @return array
     */
    public function getExtensions()
    {
        $originalHeaders = $this->getHeaders()->get('Sec-WebSocket-Extensions');
        if (!is_array($originalHeaders)) {
            $originalHeaders = [$originalHeaders];
        }

        $extensionHeaders = [];
        $extensions = [];

        foreach ($originalHeaders as $extensionHeader) {
            $extensionHeaders = array_merge($extensionHeaders, array_map('trim', explode(',', $extensionHeader)));
        }

        foreach ($extensionHeaders as $extension) {
            $explodingHeader = explode(';', $extension);
            $extensionName = trim($explodingHeader[0]);
            $extensions[$extensionName] = [];

            if (count($explodingHeader)) {
                unset($explodingHeader[0]); // removing the name of the extension
                foreach($explodingHeader as $variable) {
                    $explodeVariable = explode('=', $variable);

                    // The value can be with or without quote. We need to remove extra quotes.
                    $value = preg_replace('/^"(.+)"$/', '$1', trim($explodeVariable[1]));
                    $value = str_replace('\\"', '"', $value);

                    $extensions[$extensionName][trim($explodeVariable[0])] = $value;
                }
            }
        }

        return $extensions;
    }

    /**
     * @param string $requestString
     * @return Request
     * @throws HttpException
     */
    public static function create(string $requestString)
    {
        $request = new Request;

        $lines = explode("\r\n", $requestString);
        Request::initRequest($lines[0], $request);

        unset($lines[0]);
        Request::initHeaders($lines, $request);

        if (empty($request->getHeaders()->get('Sec-WebSocket-Key')) || empty($request->getHeaders()->get('Upgrade')) || $request->getHeaders()->get('Upgrade') !== 'websocket') {
            throw new HttpException(sprintf("The request is not a websocket upgrade request, received:\n%s", $requestString));
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
        $httpElements = explode(' ', $firstLine);

        if (count($httpElements) < 3) {
            throw Request::createNotHttpException($firstLine);
        }

        $httpElements[2] = trim($httpElements[2]);
        if (!preg_match('/HTTP\/.+/', $httpElements[2])) {
            throw Request::createNotHttpException($firstLine);
        }
        $request->setHttpVersion($httpElements[2]);

        if (!in_array($httpElements[0], ['POST', 'GET', 'PUT', 'DELETE'])) {
            throw new HttpException(
                sprintf('Request not supported, only POST, GET, PUT, and DELETE are supported. "%s" received.', $httpElements[0])
            );
        }

        $request->setMethod($httpElements[0]);
        $request->setUri($httpElements[1]);
    }

    private static function createNotHttpException($line)
    {
        return new HttpException(
            sprintf('The request is not an http request. "%s" received.', $line)
        );
    }

    protected static function initHeaders(array $headers, Request $request)
    {
        foreach ($headers as $header) {
            $cuttedHeader = explode(':', $header);
            $request->addHeader(trim($cuttedHeader[0]), trim(str_replace($cuttedHeader[0] . ':', '', $header)));
        }
    }
}
