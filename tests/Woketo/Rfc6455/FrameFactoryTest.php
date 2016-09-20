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
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Utils\BitManipulation;

class FrameFactoryTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals($data, BitManipulation::hexArrayToString(['88', '02', '03', 'E8']));
    }
}
