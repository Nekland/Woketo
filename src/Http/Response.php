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
use Nekland\Woketo\Exception\Http\IncompleteHttpMessageException;
use React\Socket\ConnectionInterface;

/**
 * Class Response
 *
 * @internal
 */
class Response extends AbstractHttpMessage
{
    const SWITCHING_PROTOCOLS = '101 Switching Protocols';
    const BAD_REQUEST = '400 Bad Request';

    /**
     * @var string For example "404 Not Found"
     */
    private $httpResponse;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $reason;

    public function __construct()
    {
        $this->setHttpVersion('HTTP/1.1');
    }

    /**
     * @param string $httpResponse
     * @return Response
     */
    public function setHttpResponse($httpResponse)
    {
        $this->httpResponse = $httpResponse;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return (int) $this->statusCode;
    }

    /**
     * @param int $statusCode
     * @return Response
     */
    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     * @return Response
     */
    public function setReason(string $reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return string
     */
    public function getAcceptKey()
    {
        return $this->getHeader('Sec-WebSocket-Accept');
    }

    /**
     * @param ConnectionInterface $stream
     */
    public function send(ConnectionInterface $stream)
    {
        $stringResponse = $this->getHttpVersion() . ' ' . $this->httpResponse . "\r\n";

        foreach ($this->getHeaders() as $name => $content) {
            $stringResponse .= $name . ': '. $content . "\r\n";
        }

        // No content to concatenate
        $stringResponse .= "\r\n";

        $stream->write($stringResponse);
    }

    public static function createSwitchProtocolResponse()
    {
        $response = new Response();

        $response->setHttpResponse(Response::SWITCHING_PROTOCOLS);
        $response->addHeader('Upgrade', 'websocket');
        $response->addHeader('Connection', 'Upgrade');

        return $response;
    }

    /**
     * @param string $data
     * @return Response
     * @throws HttpException
     */
    public static function create(string &$data) : Response
    {
        if (!\preg_match('/\\r\\n\\r\\n/', $data)) {
            throw new IncompleteHttpMessageException();
        }

        // Split response headers from potential content
        $exploded = explode("\r\n\r\n", $data);
        $responseString = '';

        if (count($exploded) > 1) {
            // Removes the request keep content in data reference
            $responseString = $exploded[0];
            unset($exploded[0]);
            $data = implode("\r\n\r\n", $exploded);
        }

        $response = new Response();

        $lines = \explode("\r\n", $responseString);
        Response::initResponse($lines[0], $response);

        unset($lines[0]);
        Response::initHeaders($lines, $response);

        if (strtolower($response->getHeader('Upgrade')) !== 'websocket') {
            throw new HttpException('Missing or wrong upgrade header.');
        }
        if (strtolower($response->getHeader('Connection')) !== 'upgrade') {
            throw new HttpException('Missing "Connection: Upgrade" header.');
        }

        return $response;
    }

    /**
     * @param string   $firstLine
     * @param Response $response
     * @throws HttpException
     */
    protected static function initResponse(string $firstLine, Response $response)
    {
        $httpElements = \explode(' ', $firstLine);

        if (!\preg_match('/HTTP\/[1-2\.]+/',$httpElements[0])) {
            throw Response::createNotHttpException($firstLine);
        }
        $response->setHttpVersion($httpElements[0]);
        unset($httpElements[0]);

        if ($httpElements[1] != 101) {
            throw new HttpException(
                sprintf('Attempted 101 response but got %s, message: %s', $httpElements[1], $firstLine)
            );
        }
        $response->setStatusCode($httpElements[1]);
        unset($httpElements[1]);

        $response->setReason(implode(' ', $httpElements));
    }
}
