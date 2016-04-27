<?php

/**
 * This file is a part of a nekland library
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455;

use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Rfc6455\ServerHandshake;

class ServerHandshakeTest extends \PHPUnit_Framework_TestCase
{
    public function testItSignWebsocket()
    {
        $handshake = new ServerHandshake();

        // From https://tools.ietf.org/html/rfc6455#section-1.3
        $this->assertEquals($handshake->sign('dGhlIHNhbXBsZSBub25jZQ=='), 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=');
    }

    public function testItSignResponse()
    {
        $response = $this->prophesize(Response::class);
        $response->addHeader('Sec-WebSocket-Accept', 's3pPLMBiTxaQ9kYGzzhZRbK+xOo=')->shouldBeCalled();
        $request = $this->prophesize(Request::class);
        $request->getHeader('Sec-WebSocket-Key')->willReturn('dGhlIHNhbXBsZSBub25jZQ==');

        $handshake = new ServerHandshake();
        $handshake->sign($request->reveal(), $response->reveal());
    }

    /**
     * @dataProvider getWrongWebsocketRequests
     */
    public function testItInvalidWrongRequests($headers, $method, $uri, $httpVersion)
    {
        $request = $this->prophesize('Nekland\Woketo\Http\Request');
        $request->getHeaders()->willReturn($headers);
        $request->getMethod()->willReturn($method);
        $request->getUri()->willReturn($uri);
        $request->getHttpVersion()->willReturn($httpVersion);


        $this->expectException('Nekland\Woketo\Exception\WebsocketException');

        $handshake = new ServerHandshake();
        $handshake->verify($request->reveal());
    }

    public function getWrongWebsocketRequests()
    {
        return [
            // wrong method
            [
                [
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                    "Upgrade" => "websocket",
                ],
                'POST',
                '/foo',
                'HTTP/1.1'
            ],
            // Wrong http version
            [
                [
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                    "Upgrade" => "websocket",
                ],
                'GET',
                '/foo',
                'HTTP/1.0'
            ],
            // Missing upgrade
            [
                [
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Sec-WebSocket-Key" => "nm7Ml8Q7dGJGWWdqnfM7AQ==",
                ],
                'GET',
                '/foo',
                'HTTP/1.1'
            ],
            // Missing key
            [
                [
                    "Host" => "127.0.0.1:8088",
                    "Sec-WebSocket-Extensions"=> "permessage-deflate",
                    "Upgrade" => "websocket",
                ],
                'GET',
                '/foo',
                'HTTP/1.1'
            ],
        ];
    }
}
