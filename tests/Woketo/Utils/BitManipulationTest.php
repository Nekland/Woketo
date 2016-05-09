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

    //
    // Providers
    //

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
