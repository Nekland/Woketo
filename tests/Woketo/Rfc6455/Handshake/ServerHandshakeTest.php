<?php

/**
 * This file is a part of a nekland library
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455\Handshake;

use Nekland\Woketo\Http\HttpHeadersBag;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Rfc6455\Handshake\ServerHandshake;
use PHPUnit\Framework\TestCase;

class ServerHandshakeTest extends TestCase
{
    public function testItSignWebSocket()
    {
        $handshake = new ServerHandshake();

        // From https://tools.ietf.org/html/rfc6455#section-1.3
        $this->assertEquals($handshake->sign(null, 'dGhlIHNhbXBsZSBub25jZQ=='), 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');
    }

    public function testItSignResponse()
    {
        $response = $this->prophesize(Response::class);
        $response->addHeader('Sec-WebSocket-Accept', 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=')->shouldBeCalled();

        $handshake = new ServerHandshake();
        $handshake->sign($response->reveal(), 'dGhlIHNhbXBsZSBub25jZQ==');
    }

    /**
     * @dataProvider getWrongWebsocketRequests
     */
    public function testItInvalidWrongRequests($headers, $method, $uri, $httpVersion, $exception = 'Nekland\Woketo\Exception\WebSocketException')
    {
        $request = $this->prophesize('Nekland\Woketo\Http\Request');
        $request->getHeaders()->willReturn($headers);
        $request->getMethod()->willReturn($method);
        $request->getUri()->willReturn($uri);
        $request->getHttpVersion()->willReturn($httpVersion);


        $this->expectException($exception);

        $handshake = new ServerHandshake();
        $handshake->verify($request->reveal());
    }

    /**
     * @dataProvider getGoodWebsocketRequests
     */
    public function testItvalidGoodRequests($headers, $method, $uri, $httpVersion)
    {

        $request = $this->prophesize('Nekland\Woketo\Http\Request');
        $request->getHeaders()->willReturn($headers);
        $request->getMethod()->willReturn($method);
        $request->getUri()->willReturn($uri);
        $request->getHttpVersion()->willReturn($httpVersion);

        $handshake = new ServerHandshake();
        $this->assertEquals($handshake->verify($request->reveal()), true);
    }

    public function getGoodWebsocketRequests()
    {
        return [
            [
                new HttpHeadersBag([
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                    "Sec-WebSocket-Version" => 13,
                    "Upgrade" => "websocket",
                ]),
                'GET',
                '/foo',
                'HTTP/1.1'
            ],
            [
                new HttpHeadersBag([
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                    "Sec-WebSocket-Version" => 13,
                    "Upgrade" => "Websocket",
                ]),
                'GET',
                '/foo',
                'HTTP/1.1'
            ],
        ];
    }

    public function getWrongWebsocketRequests()
    {
        return [
            // wrong method
            [
                new HttpHeadersBag([
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                    "Sec-WebSocket-Version" => 13,
                    "Upgrade" => "websocket",
                ]),
                'POST',
                '/foo',
                'HTTP/1.1'
            ],
            // Wrong http version
            [
                new HttpHeadersBag([
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                    "Sec-WebSocket-Version" => 13,
                    "Upgrade" => "websocket",
                ]),
                'GET',
                '/foo',
                'HTTP/1.0'
            ],
            // Missing upgrade
            [
                new HttpHeadersBag([
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                    "Sec-WebSocket-Version" => 13,
                ]),
                'GET',
                '/foo',
                'HTTP/1.1'
            ],
            // Missing key
            [
                new HttpHeadersBag([
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Upgrade" => "websocket",
                    "Sec-WebSocket-Version" => 13,
                ]),
                'GET',
                '/foo',
                'HTTP/1.1'
            ],
            // Unsupported version
            // https://tools.ietf.org/html/rfc6455#section-4.4
            [
                new HttpHeadersBag([
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                    "Sec-WebSocket-Version" => 2,
                    "Upgrade" => "websocket",
                ]),
                'GET',
                '/foo',
                'HTTP/1.1',
                'Nekland\Woketo\Exception\WebsocketVersionException'
            ],
        ];
    }
}
