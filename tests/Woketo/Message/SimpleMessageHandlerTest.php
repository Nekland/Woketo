<?php

/**
 * This file is a part of a nekland library
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
namespace Test\Woketo\Message;

use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Message\SimpleMessageHandler;
use Nekland\Woketo\Server\Connection;
use PHPUnit\Framework\TestCase;

class SimpleMessageHandlerTest extends TestCase
{
    private $instance;

    public function setUp()
    {
        $this->instance = new SimpleMessageHandlerImplementation();
    }

    public function testItDoNothingOnConnection()
    {
        \ob_start();
        $res = $this->instance->onConnection($this->prophesize(Connection::class)->reveal());
        $out = \ob_get_clean();

        $this->assertNull($res);
        $this->assertEquals('', $out);
    }

    public function testItEchosOnError()
    {
        \ob_start();
        $this->instance->onError(new WebsocketException('foobar'), $this->prophesize(Connection::class)->reveal());
        $out = \ob_get_clean();

        $this->assertContains('foobar', $out);
    }

    public function testItDoNothingOnDisconnection()
    {
        \ob_start();
        $res = $this->instance->onDisconnect($this->prophesize(Connection::class)->reveal());
        $out = \ob_get_clean();

        $this->assertNull($res);
        $this->assertEquals('', $out);
    }
}

class SimpleMessageHandlerImplementation extends SimpleMessageHandler
{
    public function onMessage(string $data, AbstractConnection $connection) {}
    public function onBinary(string $data, AbstractConnection $connection) {}
}
