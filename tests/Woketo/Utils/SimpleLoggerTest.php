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


use Nekland\Woketo\Utils\SimpleLogger;
use PHPUnit\Framework\TestCase;

class SimpleLoggerTest extends TestCase
{
    public function testItEchoesLog()
    {
        $logger = new SimpleLogger();

        \ob_start();
        $logger->info('Just some information.');
        $logger->critical('God, something went wrong !');

        $res = \ob_get_clean();

        $this->assertContains('God, something went wrong !', $res);
        $this->assertNotContains('Just some information.', $res);
    }

    public function testItEchoesEverythingInDebugMode()
    {
        $logger = new SimpleLogger(true);

        \ob_start();
        $logger->critical('Oops!');
        $logger->debug('Normal log');
        $res = \ob_get_clean();

        $this->assertContains('Oops!', $res);
        $this->assertContains('Normal log', $res);
    }
}
