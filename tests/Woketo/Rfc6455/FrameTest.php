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


use Nekland\Woketo\Exception\Frame\ControlFrameException;
use Nekland\Woketo\Exception\Frame\IncompleteFrameException;
use Nekland\Woketo\Exception\Frame\TooBigControlFrameException;
use Nekland\Woketo\Exception\Frame\WrongEncodingException;
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
        $raw = BitManipulation::hexArrayToString('8a', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58');
        $helloUnmaskedPingFrame = new Frame($raw);

        $this->assertSame($helloUnmaskedPingFrame->isMasked(), true);
        $this->assertSame($helloUnmaskedPingFrame->isFinal(), true);
        $this->assertSame($helloUnmaskedPingFrame->getPayload(), 'Hello');
        $this->assertSame($helloUnmaskedPingFrame->getOpcode(), Frame::OP_PONG);
        $this->assertSame($helloUnmaskedPingFrame->getRawData(), $raw);
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
     * When I retrieve less than minimum data for a frame (which is 4 bytes).
     */
    public function testItThrowMissingDataExceptionWhenFrameIsNotStarted()
    {
        $raw = BitManipulation::hexArrayToString('81');

        $this->expectException(IncompleteFrameException::class);

        $frame = new Frame($raw);
    }

    public function testItTakesOnlyFirstWebsocketFrameFromEntryData()
    {
        // 2 frames masked with `Hello` as content
        $entryData = BitManipulation::hexArrayToString('81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58', '81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58');
        $firstDataFrame = BitManipulation::hexArrayToString('81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58');

        $frame = new Frame($entryData);

        $this->assertSame($frame->getRawData(), $firstDataFrame);
        $this->assertSame($frame->getPayload(), 'Hello');
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

    public function testItSupportsFrameWith65536PayloadFromRawData()
    {
        $payload = file_get_contents(__DIR__ . '/../../fixtures/65536.data');
        $rawData = BitManipulation::hexArrayToString('81', '7F', '00', '00', '00', '00', '00', '01', '00', '00') . $payload;

        $frame = new Frame($rawData);

        $this->assertSame($rawData, $frame->getRawData());
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

    /**
     * @dataProvider getIsOrNotControlFrame
     */
    public function testIsAControlFrame($result, $frame)
    {
        $frame = new Frame($frame);
        $this->assertSame($result, $frame->isControlFrame());
    }

    public function getIsOrNotControlFrame()
    {
        return [
            [true, BitManipulation::hexArrayToString(['89', '00'])],//ping
            [true, BitManipulation::hexArrayToString(['8A', '00'])],//pong
            [true, BitManipulation::hexArrayToString(['88', '00'])],//close
            [true, BitManipulation::hexArrayToString(['8B', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['8C', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['8D', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['8E', '00'])],//reserved
            [true, BitManipulation::hexArrayToString(['8F', '00'])],//reserved
            [false, BitManipulation::hexArrayToString(['82', '00'])],//binary
            [false, BitManipulation::hexArrayToString(['80', '00'])],//continue
            [false, BitManipulation::hexArrayToString(['81', '00'])],//text,
         ];
    }

    public function testItChecksAGoodFrameText()
    {
        $frame = BitManipulation::hexArrayToString(['81', '9d', '22', '4a', '47', '4d', '6a', '2f', '2b', '21', '4d', '67', '85', 'f8', '62', '89', 'd8', '8e', '94', '89', 'e3', '8e', '9e', '89', 'e7', '8e', '83', '67', '12', '19', '64', '67', '7f', '6c', '03']);
        $frame = new Frame($frame);
        Frame::checkFrame($frame);
    }

    public function testItChecksAGoodControlFrame()
    {
        $frame = BitManipulation::hexArrayToString(['89','88','b5','c7','6d','58','b5','38','93','a5','49','3c','6d','a7']);
        $frame = new Frame($frame);
        Frame::checkFrame($frame);
    }

    public function testItThrowsWrongEncoding()
    {
        $this->expectException(WrongEncodingException::class);

        $frame = BitManipulation::hexArrayToString(['81','94','e8','e7','96','54','26','5d','77','e9','51','28','15','9a','54','29','23','b9','48','67','f3','30','81','93','f3','30']);
        $frame = new Frame($frame);
        Frame::checkFrame($frame);
    }

    public function testItThrowsTooBigControlFrame()
    {
        $this->expectException(TooBigControlFrameException::class);

        $frame = BitManipulation::hexArrayToString(['89','7e','00','7e','00','00 ','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','23','a9','af','ec','ec','ec','ec','ec','ec']);
        $frame = new Frame($frame);
        Frame::checkFrame($frame);
    }

    public function testItThrowsControlFrame()
    {
        $this->expectException(ControlFrameException::class);

        $frame = BitManipulation::hexArrayToString(['09','88','b5','c7','6d','58','b5','38','93','a5','49','3c','6d','a7']);
        $frame = new Frame($frame);
        Frame::checkFrame($frame);
    }
}
