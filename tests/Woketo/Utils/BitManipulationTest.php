<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Utils;

use Nekland\Woketo\Utils\BitManipulation;

class BitManipulationTest extends \PHPUnit_Framework_TestCase
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

        $this->assertEquals($realRes, $res);
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

        $this->assertEquals($realRes, $res);
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

        $this->assertEquals(BitManipulation::partOfByte($byte, $n), $res);
    }

    /**
     * @dataProvider arrayOfHexProvider
     *
     * @param string[] $hexParts
     * @param string   $result
     */
    public function testItTransformsArrayOfHexToString(array $hexParts, string $result)
    {
        $this->assertEquals(BitManipulation::hexArrayToString($hexParts), $result);
    }

    /**
     * @dataProvider bytesFromToProvider
     *
     * @param int      $frame
     * @param int      $from
     * @param int      $to
     * @param null|int $res
     */
    public function testItGetBytesFromToByteNumber($frame, $from, $to, $res = null)
    {
        if (!is_int($res)) {
            $this->expectException($res);
        }

        $this->assertSame(BitManipulation::bytesFromTo($frame, $from, $to), $res);
    }

    public function testItTransformIntFrameToString()
    {
        $this->assertSame(BitManipulation::intToString(6382179), 'abc');
    }

    public function testItTransformStringFrameToInt()
    {
        $this->assertSame(BitManipulation::stringToInt('abc'), 6382179);
    }

    //
    // Providers
    //

    public function bytesFromToProvider()
    {
        return [
            [16711850, 2, 3, 170],
            [16711850, 1, 2, 65280],
            [16711850, 1, 3, 16711850],
            [16711850, -1, 2, '\InvalidArgumentException'],
            [-16711850, 1, 2, '\InvalidArgumentException'],
            [16711850, 1, 9, '\InvalidArgumentException'],
            ['abcdef', 1, 2, 24930],
            [new \SplObjectStorage, 1, 2, '\InvalidArgumentException'],
            ['abc', 2, 5, '\InvalidArgumentException'],
            [
                BitManipulation::hexArrayToString(
                    ['81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58']
                ), 3, 6, 939139389
            ]
        ];
    }

    public function arrayOfHexProvider()
    {
        return [
            [['6c'], 'l'],
            [['21', '51', '6f'], '!Qo'],
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
            [34815, 1, 135],
            [34815, 2, 255],
            ['_7P!gij', 1, 95],
            ['_7P!gij', 2, 55],
            ['_7P!gij', 7, 106],

            // Failure
            [-10, 1],
            [new \SplObjectStorage, 2],
            ['gdgdf_7P)', 10],
            ['gdgdf_7P)', -10],
            [128, 2],
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
