<?php
use Nekland\Woketo\Http\Response;

/**
 * This file is a part of a nekland library
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testItGenerateString()
    {
        $stream = $this->prophesize('React\Stream\Stream');
        $stream->write(
            "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=\r\n"
        );

        $response = new Response();

        $response->addHeader('Upgrade', 'websocket');
        $response->addHeader('Connection', 'Upgrade');
        $response->addHeader('Sec-WebSocket-Accept', 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');
        $response->setHttpResponse(Response::SWITCHING_PROTOCOLS);

        $response->send($stream->reveal());
    }
}
