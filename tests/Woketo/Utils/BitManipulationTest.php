<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Utils;

use Nekland\Woketo\Utils\BitManipulation;
use PHPUnit\Framework\TestCase;

class BitManipulationTest extends TestCase
{
    /**
     * @dataProvider bitInBytesDataProvider
     *
     * @param int      $byte
     * @param int      $n
     * @param int|null $res
     */
    public function testItRetrieveNthBit($byte, $n, $res = null)
    {
        if ($res === null) {
            $this->expectException('\InvalidArgumentException');
        }

        $realRes = BitManipulation::nthBit($byte, $n);

        $this->assertEquals($res, $realRes);
    }

    /**
     * @dataProvider bytesInFramesDataProvider
     *
     * @param int      $bytes
     * @param int      $n
     * @param int|null $res
     */
    public function testItRetrieveNthByte($bytes, $n, $res = null)
    {
        if ($res === null) {
            $this->expectException('\InvalidArgumentException');
        }

        $realRes = BitManipulation::nthByte($bytes, $n);

        $this->assertEquals($res, $realRes);
    }

    /**
     * @dataProvider partInBytesDataProvider
     *
     * @param int      $byte
     * @param int      $n
     * @param null|int $res
     */
    public function testItRetrieveNthOctal($byte, $n, $res = null)
    {
        if ($res === null) {
            $this->expectException('\InvalidArgumentException');
        }

        $this->assertEquals($res, BitManipulation::partOfByte($byte, $n));
    }

    /**
     * @dataProvider arrayOfHexProvider
     *
     * @param string[] $hexParts
     * @param string   $result
     */
    public function testItTransformsArrayOfHexToString(array $hexParts, string $result)
    {
        $this->assertEquals($result, BitManipulation::hexArrayToString($hexParts));
    }

    /**
     * @dataProvider bytesFromToProvider
     *
     * @param int      $frame
     * @param int      $from
     * @param int      $to
     * @param null|int $res
     */
    public function testItGetBytesFromToByteNumber($frame, $from, $to, $force8bytes = false, $res = null)
    {
        if (!is_int($res)) {
            $this->expectException($res);
        }

        $this->assertSame($res, BitManipulation::bytesFromTo($frame, $from, $to));
    }

    public function testItTransformIntFrameToString()
    {
        $this->assertSame(BitManipulation::intToBinaryString(6382179), 'abc');
        $this->assertSame(BitManipulation::intToBinaryString(1000), BitManipulation::hexArrayToString(['03', 'E8']));
        $this->assertSame(BitManipulation::intToBinaryString(1000, 2), BitManipulation::hexArrayToString(['03', 'E8']));
        $this->assertSame(BitManipulation::intToBinaryString(1000, 4), BitManipulation::hexArrayToString(['00', '00', '03', 'E8']));
        $this->assertSame(BitManipulation::intToBinaryString(33024), BitManipulation::hexArrayToString(['81', '00']));
    }

    public function testItTransformStringFrameToInt()
    {
        $this->assertSame(BitManipulation::binaryStringtoInt('abcde'), 418262508645);
        $this->assertSame(BitManipulation::binaryStringtoInt('abcd'), 1633837924);
        $this->assertSame(BitManipulation::binaryStringtoInt('abc'), 6382179);
        $this->assertSame(BitManipulation::binaryStringtoInt('ab'), 24930);
        $this->assertSame(BitManipulation::binaryStringtoInt('a'), 97);
    }

    /**
     * @dataProvider frameToHexProvider
     * @param $a
     * @param $b
     */
    public function testItTransformToHex($a, $b)
    {
        $this->assertSame($a, BitManipulation::binaryStringToHex($b));
    }

    public function testItRetrieveSubFrames()
    {
        $this->assertSame('bcd', BitManipulation::bytesFromToString('abcdefg', 1, 3));
        $this->assertSame('ef', BitManipulation::bytesFromToString('abcdefg', 4, 2, BitManipulation::MODE_PHP));
    }

    //
    // Providers
    //

    public function frameToHexProvider()
    {
        return [
            [
                '0000',
                BitManipulation::hexArrayToString(['00', '00'])
            ],
            [
                '616263',
                'abc'
            ],
            [
                '8900818537fa213d7f9f4d51588900',
                BitManipulation::hexArrayToString(
                    '89', '00',
                    '81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58',
                    '89', '00'
                ),
            ]
        ];
    }

    public function bytesFromToProvider()
    {
        return [
            [16711850, 2, 2, false, 170],
            [16711850, 0, 1, false, 65280],
            [16711850, 0, 2, false, 16711850],
            [16711850, -1, 2, false, '\InvalidArgumentException'],
            [-16711850, 1, 2, false, '\InvalidArgumentException'],
            [16711850, 1, 3, false, '\InvalidArgumentException'],
            ['abcdef', 1, 2, false, 25187],
            ['abcdef', 1, 3, false, 6447972],
            [new \SplObjectStorage, 1, 2, false, '\InvalidArgumentException'],
            ['abc', 2, 5, false, '\InvalidArgumentException'],
            ['abc', 1, 3, false, '\InvalidArgumentException'],
            ['abc', 0, 0, false, 97],
            [
                BitManipulation::hexArrayToString(
                    ['81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58']
                ), 3, 6, false, 4196482431
            ],
            [
                BitManipulation::hexArrayToString(
                    ['80', '00', '00', '00', '00', '00', '00', '00', 'a5', '45']
                ), 0, 7, true, (int) -9223372036854775808
            ],
        ];
    }

    public function arrayOfHexProvider()
    {
        return [
            [['6c'], 'l'],
            [['21', '51', '6f'], '!Qo'],
            [['00', '00', '6c', '00'], chr(0) . chr(0) . 'l' . chr(0)]
        ];
    }

    public function partInBytesDataProvider()
    {
        return [
            [135, 1, 8],
            [135, 2, 7],
            [135, 10],
        ];
    }

    public function bytesInFramesDataProvider()
    {
        return [
            // Success
            [34815, 0, 135],
            [34815, 1, 255],
            ['_7P!gij', 0, 95],
            ['_7P!gij', 1, 55],
            ['_7P!gij', 6, 106],

            // Failure
            [-10, 1],
            [new \SplObjectStorage, 2],
            ['gdgdf_7P)', 10],
            ['gdgdf_7P)', -10],
            [128, 1],
        ];
    }

    public function bitInBytesDataProvider()
    {
        return [
            // Success
            [128, 1, 1],
            [135, 1, 1],
            [135, 7, 1],
            [135, 2, 0],

            // Failure
            [256, 1],
            [-1, 2],
            [2, -3],
            [2, 0],
        ];
    }
}
