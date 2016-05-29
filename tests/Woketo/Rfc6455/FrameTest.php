<?php
/**
 * This file is a part of a nekland library
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455;


use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Utils\BitManipulation;

/**
 * Class FrameTest
 *
 * These tests uses examples from the RFC:
 * https://tools.ietf.org/html/rfc6455#section-5.7
 */
class FrameTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testUnmaskedFrameContainingHello()
    {
        $helloUnmaskedFrame = new Frame(
            BitManipulation::hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f'])
        );

        $this->assertSame($helloUnmaskedFrame->getPayloadLength(), 5);
        $this->assertSame($helloUnmaskedFrame->isMasked(), false);
        $this->assertSame($helloUnmaskedFrame->isFinal(), true);
        $this->assertSame($helloUnmaskedFrame->getPayload(), 'Hello');
    }

    public function testMaskedFrameContainingHello()
    {
        // Note : there is an error in the RFC on the 8th bit !
        $helloMaskedFrame = new Frame(
            BitManipulation::hexArrayToString(
                ['81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58']
            )
        );

        $this->assertSame($helloMaskedFrame->isMasked(), true);
        $this->assertSame($helloMaskedFrame->isFinal(), true);
        $this->assertSame(BitManipulation::stringToInt($helloMaskedFrame->getMaskingKey()), 939139389);
        $this->assertSame($helloMaskedFrame->getPayload(), 'Hello');
    }

    public function testPingUnmaskedFrameContainingHello()
    {
        $helloUnmaskedPingFrame = new Frame(
            BitManipulation::hexArrayToString('89', '05', '48', '65', '6c', '6c', '6f')
        );

        $this->assertSame($helloUnmaskedPingFrame->isMasked(), false);
        $this->assertSame($helloUnmaskedPingFrame->isFinal(), true);
        $this->assertSame($helloUnmaskedPingFrame->getPayload(), 'Hello');
        $this->assertSame($helloUnmaskedPingFrame->getOpcode(), Frame::OP_PING);
    }

    public function testPongMaskedFrameContainingHello()
    {
        $helloUnmaskedPingFrame = new Frame(
            BitManipulation::hexArrayToString('8a', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58')
        );

        $this->assertSame($helloUnmaskedPingFrame->isMasked(), true);
        $this->assertSame($helloUnmaskedPingFrame->isFinal(), true);
        $this->assertSame($helloUnmaskedPingFrame->getPayload(), 'Hello');
        $this->assertSame($helloUnmaskedPingFrame->getOpcode(), Frame::OP_PONG);
    }

    /**
     * @dataProvider frameDataGenerationTestProvider
     *
     * @param string $maskingKey
     * @param string $payload
     * @param int    $opcode
     * @param string $expected
     */
    public function testItGeneratesFrameData($maskingKey, $payload, $opcode, $expected)
    {
        $frame = new Frame();

        $frame->setFinal(true);
        $frame->setMasked(null !== $maskingKey);
        $frame->setMaskingKey($maskingKey);
        $frame->setPayload($payload);
        $frame->setOpcode($opcode);

        $this->assertSame($expected, $frame->getPayload());
    }

    public function frameDataGenerationTestProvider()
    {
        return [
            [
                BitManipulation::intToString(939139389),
                'Hello',
                Frame::OP_TEXT,
                BitManipulation::hexArrayToString(
                    ['81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58']
                )
            ]
        ];
    }
}
