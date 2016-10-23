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

    public function testItSupportsPayloadOn8Bits()
    {
        // Hello unmasked frame with payload size on 8 bytes.
        $frame = new Frame(
            BitManipulation::hexArrayToString(['81', '7F', '00', '00', '00', '00', '00', '00', '00', '05', '48', '65', '6c', '6c','6f'])
        );

        $this->assertSame($frame->getPayloadLength(), 5);
        $this->assertSame($frame->getPayload(), 'Hello');
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

    public function testItSupportsEmptyFrames()
    {
        $frame = new Frame();
        $frame->setPayload('');
        $frame->setOpcode(Frame::OP_TEXT);

        $this->assertSame($frame->getRawData(), BitManipulation::hexArrayToString([
            '81', '00'
        ]));
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
        $frame->setMaskingKey($maskingKey);
        $frame->setPayload($payload);
        $frame->setOpcode($opcode);

        $this->assertSame($frame->isMasked(), null !== $maskingKey);
        $this->assertSame($frame->isValid(), true);
        $this->assertSame($expected, $frame->getRawData());
    }

    public function testItGenerateFrameWith65536Bytes()
    {
        $payload = file_get_contents(__DIR__ . '/../../fixtures/65536.data');
        $expectedData = BitManipulation::hexArrayToString('81', '7F', '00', '00', '00', '00', '00', '01', '00', '00') . $payload;

        $frame = new Frame();
        $frame->setPayload($payload);
        $frame->setOpcode(Frame::OP_TEXT);

        $this->assertSame($expectedData, $frame->getRawData());
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
