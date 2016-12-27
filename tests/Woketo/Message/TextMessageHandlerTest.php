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


use Nekland\Woketo\Exception\UnsupportedException;
use Nekland\Woketo\Message\TextMessageHandler;
use Nekland\Woketo\Server\Connection;

class TextMessageHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testItThrowsExceptionOnBinary()
    {
        $handler = new TextMessageHandlerImplementation();
        $this->expectException(UnsupportedException::class);
        $handler->onBinary('', $this->prophesize(Connection::class)->reveal());
    }
}

class TextMessageHandlerImplementation extends TextMessageHandler
{
    public function onMessage(string $data, Connection $connection) {}
}
