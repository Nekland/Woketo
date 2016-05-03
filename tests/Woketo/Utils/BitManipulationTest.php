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
     * @param int $byte
     * @param int $n
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
