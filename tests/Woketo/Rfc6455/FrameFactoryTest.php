<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455;

use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Utils\BitManipulation;
use PHPUnit\Framework\TestCase;

class FrameFactoryTest extends TestCase
{
    /**
     * @var FrameFactory
     */
    private $factory;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = new FrameFactory();
    }

    public function testCreateCloseFrame()
    {
        $frame = $this->factory->createCloseFrame(Frame::CLOSE_NORMAL);
        $data = $frame->getRawData();

        // 1000 1000  0000 0010
        // 0000 0011  1110 1000
        // With:
        // payload: 000 0010
        // close code: 0000 0011  1110 1000
        $this->assertEquals(BitManipulation::hexArrayToString(['88', '02', '03', 'E8']), $data);
    }

    public function testCreatePongFrame()
    {
        $pingMessage = new Message();
        $pingMessage->addFrame(new Frame(BitManipulation::hexArrayToString(['89', '00'])));
        $message = $pingMessage->getContent();

        $frame = $this->factory->createPongFrame($message);
        $this->assertEquals($message, $frame->getPayload());
    }

    public function testItGeneratesMaskingKey()
    {
        $this->assertSame(4, strlen(FrameFactory::generateMask()));
    }
}
