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

use Nekland\Woketo\Exception\Http\HttpException;
use Nekland\Woketo\Http\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ResponseTest extends TestCase
{
    /**
     * @var \React\Stream\DuplexStreamInterface
     */
    private $stream;

    public function setUp()
    {

        $this->stream = $this->prophesize('React\Socket\ConnectionInterface');
    }

    public function testItGenerateString()
    {
        $response = new Response();

        $response->addHeader('Upgrade', 'websocket');
        $response->addHeader('Connection', 'Upgrade');
        $response->addHeader('Sec-WebSocket-Accept', 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');
        $response->setHttpResponse(Response::SWITCHING_PROTOCOLS);

        $httpResponse =
            "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=\r\n\r\n"
        ;
        $this->stream->write($httpResponse)->shouldBeCalled();
        $this->stream = $this->stream->reveal();
        $response->send($this->stream);
    }

    public function testItGenerateResponseForWebsocket()
    {
        $response = Response::createSwitchProtocolResponse();
        $response->addHeader('Sec-WebSocket-Accept', 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');

        $this->stream->write(Argument::any())->shouldBeCalled();
        $this->stream = $this->stream->reveal();

        $response->send($this->stream);
    }

    public function testItCreateAResponseFromText()
    {
        $str = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=\r\n\r\n";
        $response = Response::create($str);
        
        $this->assertEquals($response->getStatusCode(), '101');
        $this->assertEquals($response->getReason(), 'Switching Protocols');
        $this->assertEquals($response->getAcceptKey(), 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');
        $this->assertEquals($response->getHeader('Sec-WebSocket-Accept'), 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');
        $this->assertEquals($response->getHeader('Upgrade'), 'websocket');
    }
    
    public function testItCreateAResponseFromBadResponseText()
    {
        $str = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n\r\n";
        $response = Response::create($str);
        
        $this->assertEquals($response->getStatusCode(), '101');
        $this->assertEquals($response->getReason(), 'Switching Protocols');
        $this->assertEquals($response->getAcceptKey(), null);
        $this->assertEquals($response->getHeader('Sec-WebSocket-Accept'), null);
    }
    
    public function testItThrowsAnExceptionOnWrongResponseCode()
    {
        $this->expectException(HttpException::class);

        $str = "HTTP/1.1 200 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=\r\n\r\n";
        Response::create($str);
    }
    
    public function testItThrowsAndExceptionOnWrongUpgradeHeader()
    {
        $this->expectException(HttpException::class);
        $str = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: null\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=\r\n\r\n";
        Response::create($str);
    }
    
    public function testItThrowsAndExceptionOnWrongConnectionHeader()
    {
        $this->expectException(HttpException::class);

        $str = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: U mad?\r\n"
            . "Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=\r\n\r\n";
        Response::create($str);
    }

    public function testItRemovesUselessDataFromTakenInParameters()
    {
        $str = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n\r\n"
            . "More but useless swagg\r\n\r\n";
        Response::create($str);

        $this->assertSame($str, "More but useless swagg\r\n\r\n");
    }
}
