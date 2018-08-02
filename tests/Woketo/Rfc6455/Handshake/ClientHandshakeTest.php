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


use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Rfc6455\Handshake\ClientHandshake;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ClientHandshakeTest extends TestCase
{
    public function testItSignWith16BytesKeys()
    {
        $handshake = new ClientHandshake();

        /** @var Request $request */
        $request = $this->prophesize(Request::class);
        $request->setKey(Argument::any())->shouldBeCalled();
        $request = $request->reveal();

        $this->assertEquals(16, strlen(base64_decode($handshake->sign($request))));
    }
    
    public function testYouCannotUseItWithResponse()
    {
        $handshake = new ClientHandshake();
        
        $response = $this->prophesize(Response::class)->reveal();
        
        $this->expectException(RuntimeException::class);
        
        $handshake->sign($response, 'key');
    }
}
