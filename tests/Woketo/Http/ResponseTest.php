<?php

/**
 * This file is a part of a nekland library
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Http;

use Nekland\Woketo\Http\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \React\Stream\Stream
     */
    private $stream;

    public function setUp()
    {
        $httpResponse =
            "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=\r\n\r\n"
        ;

        $stream = $this->prophesize('React\Stream\Stream');
        $stream->write($httpResponse)->shouldBeCalled();
        $this->stream = $stream->reveal();
    }

    public function testItGenerateString()
    {
        $response = new Response();

        $response->addHeader('Upgrade', 'websocket');
        $response->addHeader('Connection', 'Upgrade');
        $response->addHeader('Sec-WebSocket-Accept', 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');
        $response->setHttpResponse(Response::SWITCHING_PROTOCOLS);

        $response->send($this->stream);
    }

    public function testItGenerateResponseForWebsocket()
    {
        $response = Response::createSwitchProtocolResponse();
        $response->addHeader('Sec-WebSocket-Accept', 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');

        $response->send($this->stream);
    }
}
