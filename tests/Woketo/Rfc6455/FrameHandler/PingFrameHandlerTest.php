<?php

namespace Test\Woketo\Rfc6455\FrameHandler;

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

use Nekland\Woketo\Exception\Frame\TooBigFrameException;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\FrameHandler\PingFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class PingFrameHandlerTest extends TestCase
{
    public function testItSupportsPingFrame()
    {

        $handler = new PingFrameHandler();
        $pingMessage = new Message();
        $pingMessage->addFrame(new Frame(BitManipulation::hexArrayToString(['89', '00'])));
        $sup = $handler->supports($pingMessage);

        $this->assertSame($sup, true);
    }

    public function testItProcessPingFrame()
    {
        $frame = new Frame();

        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['89','7F', '00', '00', '00', '00', '00', '00', '00', '05', '48', '65', '6c', '6c','6f'])));

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createPongFrame(Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());

        $frameFactory->createCloseFrame(Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());


        $socket->end()->shouldNotBeCalled();

        $handler = new PingFrameHandler();
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());

        $this->expectException(TooBigFrameException::class);
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['89','7F', '7F', '7F', '7F', '7F', '7F', '7F', '7F', '7F', '7F', '65', '6c', '6c','6f'])));
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());
    }
}
