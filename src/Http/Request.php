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

        if (empty($request->getHeaders()['Sec-WebSocket-Key']) || empty($request->getHeaders()['Upgrade']) || $request->getHeaders()['Upgrade'] !== 'websocket') {
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

    protected static function initHeaders(array $headers, $request)
    {
        foreach ($headers as $header) {
            $cuttedHeader = explode(':', $header);
            $request->addHeader(trim($cuttedHeader[0]), trim(str_replace($cuttedHeader[0] . ':', '', $header)));
        }
    }
}
