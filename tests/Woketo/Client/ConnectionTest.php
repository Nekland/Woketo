<?php

namespace Test\Woketo\Client;

use Nekland\Tools\StringTools;
use Nekland\Woketo\Client\Connection;
use Nekland\Woketo\Http\Url;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
class ConnectionTest extends TestCase
{
    public function testItProcessHandshake()
    {
        $socket = $this->prophesize(ConnectionInterface::class);
        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $messageHandler = $this->prophesize(MessageHandlerInterface::class);
        $loop = $this->prophesize(LoopInterface::class);

        $socket->write(Argument::that(function ($arg) {
            $request =
                "GET / HTTP/1.1\r\n"
                . "Host: localhost:9000\r\n"
                . "User-Agent: Woketo/2.0\r\n"
                . "Upgrade: websocket\r\n"
                . "Connection: Upgrade\r\n"
                . "Sec-WebSocket-Version: 13\r\n"
                . "Sec-WebSocket-Key:"
            ;
            return StringTools::startsWith($arg, $request);
        }))->shouldBeCalled();
        $socket->on('data', Argument::type('callable'))->shouldBeCalled();


        $promise = new Promise(function (callable $resolve, callable $reject) use ($socket) {
            $resolve($socket->reveal());
        });
        $connection = new Connection(new Url('ws://localhost:9000'), $promise, $messageProcessor->reveal(), $messageHandler->reveal(), $loop->reveal());
    }

    public function testItSendsMessagesWithMessageProcessor()
    {
        $socket = $this->prophesize(ConnectionInterface::class);
        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $messageHandler = $this->prophesize(MessageHandlerInterface::class);
        $loop = $this->prophesize(LoopInterface::class);

        $socket->write(Argument::type('string'))->shouldBeCalled();
        $socket->on('data', Argument::type('callable'))->shouldBeCalled();

        $messageProcessor->write('hello', $socket, Argument::any())->shouldBeCalled();

        $promise = new Promise(function (callable $resolve, callable $reject) use ($socket) {
            $resolve($socket->reveal());
        });
        $connection = new Connection(new Url('ws://localhost:9000'), $promise, $messageProcessor->reveal(), $messageHandler->reveal(), $loop->reveal());
        $connection->write('hello');
    }

    public function testItGivesTheLoopBack()
    {
        $socket = $this->prophesize(ConnectionInterface::class);
        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $messageHandler = $this->prophesize(MessageHandlerInterface::class);
        $promise = new Promise(function (callable $resolve, callable $reject) use ($socket) {
            $resolve($socket->reveal());
        });

        $loop = new StreamSelectLoop();
        $connection = new Connection (new Url('ws://localhost:9000'), $promise, $messageProcessor->reveal(), $messageHandler->reveal(), $loop);

        $this->assertSame($loop, $connection->getLoop());
    }
}
