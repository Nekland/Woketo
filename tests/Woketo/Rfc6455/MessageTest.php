<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455;


use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Utils\BitManipulation;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testItStackFramesAndReturnCompleteMessage()
    {
        /** @var Frame $frame1 */
        $frame1 = $this->prophesize('\Nekland\Woketo\Rfc6455\Frame');
        $frame1->getPayload()->willReturn('foo bar ');
        $frame1->isFinal()->willReturn(false);

        /** @var Frame $frame2 */
        $frame2 = $this->prophesize('\Nekland\Woketo\Rfc6455\Frame');
        $frame2->getPayload()->willReturn('baz');
        $frame2->isFinal()->willReturn(true);

        $message = new Message();
        $message->addFrame($frame1->reveal());

        $this->assertSame($message->isComplete(), false);

        $message->addFrame($frame2->reveal());

        $this->assertSame($message->isComplete(), true);
        $this->assertSame($message->getContent(), 'foo bar baz');
    }

    public function testItThrowErrorWhenMissingFrame()
    {
        /** @var Frame $frame1 */
        $frame1 = $this->prophesize('\Nekland\Woketo\Rfc6455\Frame');
        $frame1->getPayload()->willReturn('foo bar ');
        $frame1->isFinal()->willReturn(false);

        /** @var Frame $frame2 */
        $frame2 = $this->prophesize('\Nekland\Woketo\Rfc6455\Frame');
        $frame2->getPayload()->willReturn('baz');
        $frame2->isFinal()->willReturn(false);

        $message = new Message();
        $message->addFrame($frame1->reveal());
        $message->addFrame($frame2->reveal());

        $this->assertSame($message->isComplete(), false);
        
        $this->expectException('\Nekland\Woketo\Exception\MissingDataException');

        $message->getContent();
    }

    public function testItThrowExceptionWhenTooMuchMessages()
    {
        $message = new Message();

        $this->expectException('\Nekland\Woketo\Exception\LimitationException');

        for($i = 0; $i <= 20; $i++) {
            $frame = $this->prophesize('\Nekland\Woketo\Rfc6455\Frame');
            $frame->isFinal()->willReturn(false);
            $message->addFrame($frame->reveal());
        }
    }

    public function testItBufferDataToCreateFrame()
    {
        $message = new Message();

        $message->addData(BitManipulation::hexArrayToString(['01', '03', '48', '65', '6c']));

        $this->assertSame($message->isComplete(), false);

        $message->addData(BitManipulation::hexArrayToString(['80', '02', '6c', '6f']));

        $this->assertSame($message->isComplete(), true);
    }

    public function testItSupportsMessageInManyFrames()
    {
        $message = new Message();
        $message->addData(BitManipulation::hexArrayToString(['81', '05']));
        $message->addData(BitManipulation::hexArrayToString(['48', '65']));
        $message->addData(BitManipulation::hexArrayToString(['6c', '6c', '6f']));

        $this->assertSame($message->getContent(), 'Hello');
    }
}
