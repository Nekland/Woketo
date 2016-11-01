<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455\MessageHandler;


use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageHandler\WrongOpcodeHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class WrongOpcodeHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $wrongMessage;

    protected function setUp()
    {
        $this->wrongMessage = new Message();
        $this->wrongMessage->addData(BitManipulation::hexArrayToString('83','00'));
    }

    public function testItSupportsMessageWithWrongOpcode()
    {
        $handler = new WrongOpcodeHandler();

        $this->assertSame($handler->supports($this->wrongMessage), true);
    }

    public function testItCloseWithProtocolError()
    {
        $frame = new Frame();

        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $frameFactory = $this->prophesize(FrameFactory::class);
        $socket = $this->prophesize(ConnectionInterface::class);

        $frameFactory->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR, Argument::cetera())->willReturn($frame);
        $messageProcessor->write(Argument::type(Frame::class), Argument::cetera())->shouldBeCalled();
        $messageProcessor->getFrameFactory()->willReturn($frameFactory->reveal());
        $socket->end()->shouldBeCalled();

        $handler = new WrongOpcodeHandler();
        $handler->process($this->wrongMessage, $messageProcessor->reveal(), $socket->reveal());
    }
}
