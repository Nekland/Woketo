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
use Nekland\Woketo\Rfc6455\FrameHandler\CloseFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class CloseFrameHandlerTest extends TestCase
{
    public function testItProcessCloseFrame()
    {
        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        // Normal close frame without mask
        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['88', '02', '03', 'E8'])));

        $handler = new CloseFrameHandler();
        $this->assertTrue($handler->supports($message));
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());
    }

    public function testItCloseWithProtocolErrorWhenFrameIsNotValid()
    {
        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR)->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        // Normal close frame without mask
        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['F8', '02', '03', 'E8'])));

        $handler = new CloseFrameHandler();
        $this->assertTrue($handler->supports($message));
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());
    }

    /**
     * Check it return protocol error on code that should not be send
     *
     * @dataProvider getCloseCodeAndRelatedResponseCode
     */
    public function testItClosesWithProtocolErrorOnWrongCloseCode(int $codeFrameIn, int $codeFrameOut)
    {
        $frameIn = BitManipulation::intToBinaryString($codeFrameIn, 2);

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame($codeFrameOut)->willReturn(new Frame());
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        // Normal close frame without mask
        $message = new Message();
        $message->addFrame(new Frame(BitManipulation::hexArrayToString(['88', '02']).$frameIn));

        $handler = new CloseFrameHandler();
        $this->assertTrue($handler->supports($message));
        $handler->process($message, $messageProcessor->reveal(), $socket->reveal());
    }

    public function getCloseCodeAndRelatedResponseCode()
    {
        return [
            [999, Frame::CLOSE_PROTOCOL_ERROR],
            [10, Frame::CLOSE_PROTOCOL_ERROR],
            [1000, Frame::CLOSE_NORMAL],
            [1100, Frame::CLOSE_PROTOCOL_ERROR],
            [4000, Frame::CLOSE_NORMAL],
            [6000, Frame::CLOSE_PROTOCOL_ERROR],
        ];
    }
}
