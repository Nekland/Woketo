<?php

namespace Test\Woketo\Rfc6455\MessageHandler;

use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\MessageHandler\CloseFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use Prophecy\Argument;

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
class CloseFrameHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $closeFrame;

    public function setUp()
    {
        parent::setUp();

        // Normal close frame without mask
        $this->closeFrame = new Frame(BitManipulation::hexArrayToString(['88', '02', '03', 'E8']));
    }
    public function testItSupportsCloseFrame()
    {
        $handler = new CloseFrameHandler();
        $sup = $handler->supports($this->closeFrame);

        $this->assertSame($sup, true);
    }

    public function testItProcessCloseFrame()
    {
        $messageProcessor = $this->prophesize(MessageProcessor::class);
        $messageProcessor->write(Argument::type(Frame::class))->shouldBeCalled();

        $handler = new CloseFrameHandler();
        $handler->process($this->closeFrame, $messageProcessor->reveal());
    }
}
