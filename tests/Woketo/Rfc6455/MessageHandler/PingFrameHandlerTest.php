<?php

namespace Test\Woketo\Rfc6455\MessageHandler;

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageHandler\PingFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class PingFrameHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $pingMessage;

    public function setUp()
    {
        parent::setUp();

        // Normal ping frame without payload
        $this->pingMessage = new Message();
        $this->pingMessage->addFrame(new Frame(BitManipulation::hexArrayToString(['89', '00'])));
    }

    public function testItSupportsPingFrame()
    {
        $handler = new PingFrameHandler();
        $sup = $handler->supports($this->pingMessage);

        $this->assertSame($sup, true);
    }

    public function testItProcessPingFrame()
    {
        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createPongFrame(Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());

        $frameFactory->createCloseFrame(Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());

        $socket->end()->shouldBeCalled();

        $handler = new PingFrameHandler();
        $handler->process($this->pingMessage, $messageProcessor->reveal(), $socket->reveal());
    }
}
