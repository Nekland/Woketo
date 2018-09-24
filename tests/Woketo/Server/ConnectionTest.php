<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Server;

use Evenement\EventEmitterTrait;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Handshake\ServerHandshake;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Server\Connection;
use Nekland\Woketo\Utils\BitManipulation;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

class ConnectionTest extends TestCase
{
    public function testItSupportsTextMessage()
    {
        // Data
        $handshake = $this->getHandshake();
        $helloFrame = BitManipulation::hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']);

        // Mocks
        $reactMock = new ReactConnectionMock();
        $handler = $this->prophesize(MessageHandlerInterface::class);
        $loop = $this->prophesize(LoopInterface::class);
        /** @var MessageProcessor $processor */
        $processor = $this->prophesize(MessageProcessor::class);
        $handshakeProcessor = $this->prophesize(ServerHandshake::class);
        /** @var Message $message */
        $message = $this->prophesize(Message::class);

        $message->isComplete()->willReturn(true);
        $message->getOpcode()->willReturn(Frame::OP_TEXT);
        $message->getContent()->willReturn('Hello');
        $processor->onData($helloFrame, Argument::cetera())->willReturn([$message->reveal()]);
        $handler->onConnection(Argument::type(Connection::class))->shouldBeCalled();
        $handler->onMessage('Hello', Argument::type(Connection::class))->shouldBeCalled();

        // Init
        $connection = new Connection($reactMock, function () use ($handler) {return $handler->reveal();}, $loop->reveal(), $processor->reveal(), $handshakeProcessor->reveal());

        $reactMock->emit('data', [$handshake]);
        $reactMock->emit('data', [$helloFrame]);
    }


    public function testItSupportsBinaryMessage()
    {
        // Data
        $handshake = $this->getHandshake();
        $binary = file_get_contents(__DIR__ . '/../../fixtures/hello.data.zip');
        $binaryFrame = BitManipulation::hexArrayToString('82', '7A') . $binary;

        // Mocks
        $reactMock = new ReactConnectionMock();
        $handler = $this->prophesize(MessageHandlerInterface::class);
        $loop = $this->prophesize(LoopInterface::class);
        /** @var MessageProcessor $processor */
        $processor = $this->prophesize(MessageProcessor::class);
        $handshakeProcessor = $this->prophesize(ServerHandshake::class);
        /** @var Message $message */
        $message = $this->prophesize(Message::class);

        $message->isComplete()->willReturn(true);
        $message->getOpcode()->willReturn(Frame::OP_BINARY);
        $message->getContent()->willReturn($binary);
        $processor->onData($binaryFrame, Argument::cetera())->willReturn([$message->reveal()]);
        $handler->onConnection(Argument::type(Connection::class))->shouldBeCalled();
        $handler->onBinary($binary, Argument::type(Connection::class))->shouldBeCalled();

        // Init
        $connection = new Connection($reactMock, function () use ($handler) {return $handler->reveal();}, $loop->reveal(), $processor->reveal(), $handshakeProcessor->reveal());

        $reactMock->emit('data', [$handshake]);
        $reactMock->emit('data', [$binaryFrame]);
    }

    public function testItCallOnDisconnectOnHandlerWhenDisconnect()
    {
        // Mocks
        $reactMock = new ReactConnectionMock();
        $handler = $this->prophesize(MessageHandlerInterface::class);
        $loop = $this->prophesize(LoopInterface::class);
        /** @var MessageProcessor $processor */
        $processor = $this->prophesize(MessageProcessor::class);
        $handshakeProcessor = $this->prophesize(ServerHandshake::class);

        // Init
        $connection = new Connection($reactMock, function () use ($handler) {return $handler->reveal();}, $loop->reveal(), $processor->reveal(), $handshakeProcessor->reveal());
        $server = new ReactConnectionMock();
        $server->emit('connection', [$connection]);


        $handler->onDisconnect(Argument::type(Connection::class))->shouldBeCalled();
        $reactMock->emit('end');
    }

    private function getHandshake()
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
        . "Upgrade: websocket\r\n\r\n";
    }
}

class ReactConnectionMock implements ConnectionInterface
{
    use EventEmitterTrait;

    public function getRemoteAddress() {}

    public function isReadable(){}

    public function pause() {}

    public function resume() {}

    public function pipe(WritableStreamInterface $dest, array $options = array()) {}

    public function close() {}

    public function isWritable() {}

    public function write($data) {}

    public function end($data = null) {}

    public function getLocalAddress(){}
}
