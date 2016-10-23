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


use React\Socket\ConnectionInterface;

class Response extends AbstractHttpMessage
{
    const SWITCHING_PROTOCOLS = '101 Switching Protocols';
    const BAD_REQUEST = '400 Bad Request';

    /**
     * @var string For example "404 Not Found"
     */
    private $httpResponse;

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
}
