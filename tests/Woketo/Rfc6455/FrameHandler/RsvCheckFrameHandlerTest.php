<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455\FrameHandler;


use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\FrameHandler\RsvCheckFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class RsvCheckFrameHandlerTest extends TestCase
{
    public function testItCloseFrameOnRsvInvalid()
    {
        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        // Message with 2 frames
        // 1. "Hel" normal frame
        // 2. "lo" frame with RSV 1 = 1
        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['01', '03', '48', '65', '6c'])));
        $message->addFrame(new Frame(BitManipulation::hexArrayToString('c0', '02', '6c', '6f')));

        $handler = new RsvCheckFrameHandler();
        $this->assertTrue($handler->supports($message));
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());
    }

    public function testItDoesNotCloseWhenNoRsv()
    {
        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldNotBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldNotBeCalled();

        // Hello masked frame
        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString('81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58')));

        $handler = new RsvCheckFrameHandler();
        $this->assertFalse($handler->supports($message));
    }
}
