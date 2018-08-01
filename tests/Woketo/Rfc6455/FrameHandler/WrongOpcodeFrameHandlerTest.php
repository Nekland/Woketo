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
use Nekland\Woketo\Rfc6455\FrameHandler\WrongOpcodeFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class WrongOpcodeFrameHandlerTest extends TestCase
{
    /**
     * @dataProvider getFrameWithWrongOpCode
     */
    public function testItSupportsMessageWithWrongOpcode($result, $frame)
    {
        $handler = new WrongOpcodeFrameHandler();
        $wrongMessage = new Message();
        $wrongMessage->addFrame(new Frame($frame));

        $this->assertSame($result, $handler->supports($wrongMessage));
    }

    public function testItCloseWithProtocolError()
    {
        $wrongMessage = new Message();
        $wrongMessage->addFrame(new Frame(BitManipulation::hexArrayToString('83','00')));

        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR, Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        $handler = new WrongOpcodeFrameHandler();
        $handler->process($wrongMessage, $messageProcessor->reveal(), $socket->reveal());
    }

    public function getFrameWithWrongOpCode()
    {
        return [
            [false, BitManipulation::hexArrayToString(['80', '00'])],//continue
            [false, BitManipulation::hexArrayToString(['81', '00'])],//text
            [false, BitManipulation::hexArrayToString(['82', '00'])],//binary
            [true, BitManipulation::hexArrayToString(['83', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['84', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['85', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['86', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['87', '00'])],//reserved
            [false, BitManipulation::hexArrayToString(['88', '00'])],//close
            [false, BitManipulation::hexArrayToString(['89', '00'])],//ping
            [false, BitManipulation::hexArrayToString(['8A', '00'])],//pong
            [true, BitManipulation::hexArrayToString(['8B', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['8C', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['8D', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['8E', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['8F', '00'])],//reserved
        ];
    }
}
