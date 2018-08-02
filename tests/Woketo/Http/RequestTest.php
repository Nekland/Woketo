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

use Nekland\Woketo\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testAccessors()
    {
        $request = Request::create($this->getStandardRequest());

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUri());
        $this->assertEquals('HTTP/1.1', $request->getHttpVersion());

        $headers = $request->getHeaders();
        $headersToCheck = [
            'Host' => '127.0.0.1:8088',
            'User-Agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:45.0) Gecko/20100101 Firefox/45.0',
            'Sec-WebSocket-Key' => 'nm7Ml8Q7dGJGWWdqnfM7AQ==',
            'Sec-WebSocket-Extensions' => 'permessage-deflate',
            'Upgrade' => 'websocket'
        ];

        foreach($headersToCheck as $key => $item) {
            $this->assertEquals($headers->get($key), $item);
        }

        $this->assertSame(13, $request->getVersion());
        $this->assertSame(['permessage-deflate' => []], $request->getExtensions());
    }

    /**
     * @dataProvider getWrongWebsocketRequests
     * @param $request
     */
    public function testItThrowsGoodErrors($request)
    {
        $this->expectException('Nekland\Woketo\Exception\Http\HttpException');

        Request::create($request);
    }

    /**
     * https://tools.ietf.org/html/rfc6455#section-9.1
     */
    public function testItRetrieveExtensions()
    {
        $request = Request::create($this->getRequestWithManyExtensions());
        $this->assertSame(
            [
                'permessage-deflate' => ['baz' => '195'],
                'foo' => [],
                'bar' => []
            ],
            $request->getExtensions()
        );
    }

    public function testItAllowToCreateRequestFromScratchAndGetItAsString()
    {
        $request = Request::createClientRequest('/chat', 'www.example.com');
        $request->setVersion(13);
        $request->setKey('sOmEaWeSoMeKey');
        $request->setPort(9000);

        $this->assertSame($request->getRequestAsString(),
            "GET /chat HTTP/1.1\r\n"
            . "Host: www.example.com:9000\r\n"
            . "User-Agent: Woketo/2.0\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "Sec-WebSocket-Key: sOmEaWeSoMeKey\r\n\r\n"
        );
    }

    private function getStandardRequest()
    {
        return "GET /foo HTTP/1.1\r\n"
            . "Host: 127.0.0.1:8088\r\n"
            . "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:45.0) Gecko/20100101 Firefox/45.0\r\n"
            . "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
            . "Accept-Language: en-US,en;q=0.5\r\n"
            . "Accept-Encoding: gzip, deflate\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "Origin: null\r\n"
            . "Sec-WebSocket-Extensions: permessage-deflate\r\n"
            . "Sec-WebSocket-Key: nm7Ml8Q7dGJGWWdqnfM7AQ==\r\n"
            . "Connection: keep-alive, Upgrade\r\n"
            . "Pragma: no-cache\r\n"
            . "Cache-Control: no-cache\r\n"
            . "Upgrade: websocket\r\n\r\n"
        ;
    }

    private function getRequestWithManyExtensions()
    {
        return "GET /foo HTTP/1.1\r\n"
            . "Host: 127.0.0.1:8088\r\n"
            . "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:45.0) Gecko/20100101 Firefox/45.0\r\n"
            . "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
            . "Accept-Language: en-US,en;q=0.5\r\n"
            . "Accept-Encoding: gzip, deflate\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "Origin: null\r\n"
            . "Sec-WebSocket-Extensions: permessage-deflate; baz=195\r\n"
            . "Sec-WebSocket-Extensions: foo, bar\r\n"
            . "Sec-WebSocket-Key: nm7Ml8Q7dGJGWWdqnfM7AQ==\r\n"
            . "Connection: keep-alive, Upgrade\r\n"
            . "Pragma: no-cache\r\n"
            . "Cache-Control: no-cache\r\n"
            . "Upgrade: websocket\r\n\r\n"
        ;
    }

    public function getWrongWebsocketRequests()
    {
        return [
            // wrong http version
            [
                "GET /foo sgsggd sdgd gs\r\n"
                . "Host: 127.0.0.1:8088\r\n"
                . "Sec-WebSocket-Extensions: permessage-deflate\r\n"
                . "Sec-WebSocket-Key: nm7Ml8Q7dGJGWWdqnfM7AQ==\r\n"
                . "Upgrade: websocket\r\n\r\n"
            ],
            // wrong header
            [
                "Pgsdgsdggsggs\r\n"
                . "Host: 127.0.0.1:8088\r\n"
                . "Sec-WebSocket-Extensions: permessage-deflate\r\n"
                . "Sec-WebSocket-Key: nm7Ml8Q7dGJGWWdqnfM7AQ==\r\n"
                . "Upgrade: websocket\r\n\r\n"
            ],
            // Wrong method
            [
                "FOOBAR /foo HTTP/1.1\r\n"
                . "Host: 127.0.0.1:8088"
                . "\r\n\r\n"
            ],
        ];
    }
}
