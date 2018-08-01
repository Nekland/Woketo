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
use Nekland\Woketo\Exception\UnsupportedException;
use Nekland\Woketo\Message\BinaryMessageHandler;
use Nekland\Woketo\Server\Connection;
use PHPUnit\Framework\TestCase;

class BinaryMessageHandlerTest extends TestCase
{
    public function testItThrowExceptionOnTextMessage()
    {
        $handler = new BinaryMessageHandlerImplementation();
        $this->expectException(UnsupportedException::class);
        $handler->onMessage('', $this->prophesize(Connection::class)->reveal());
    }
}

class BinaryMessageHandlerImplementation extends BinaryMessageHandler
{
    public function onBinary(string $data, AbstractConnection $connection) {}
}
