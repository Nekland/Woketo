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
use Nekland\Woketo\Rfc6455\MessageHandler\CloseFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class CloseFrameHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $closeMessage;

    public function setUp()
    {
        parent::setUp();

        // Normal close frame without mask
        $this->closeMessage = new Message();
        $this->closeMessage->addFrame(new Frame(BitManipulation::hexArrayToString(['88', '02', '03', 'E8'])));
    }

    public function testItSupportsCloseFrame()
    {
        $handler = new CloseFrameHandler();
        $sup = $handler->supports($this->closeMessage);

        $this->assertSame($sup, true);
    }

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

        $handler = new CloseFrameHandler();
        $handler->process($this->closeMessage, $messageProcessor->reveal(), $socket->reveal());
    }
}
