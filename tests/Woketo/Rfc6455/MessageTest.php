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
        $message->addFrame($frame2->reveal());


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

        $this->expectException('\Nekland\Woketo\Exception\MissingDataException');

        $message->getContent();
    }

    public function testItThrowExceptionWhenTooMuchMessages()
    {
        $message = new Message();

        $this->expectException('\Nekland\Woketo\Exception\LimitationException');

        for($i = 0; $i < 20; $i++) {
            $message->addFrame($this->prophesize('\Nekland\Woketo\Rfc6455\Frame')->reveal());
        }
    }
}
