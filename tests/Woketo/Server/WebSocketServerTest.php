<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Server;


use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Server\WebSocketServer;

class WebSocketServerTest extends \PHPUnit_Framework_TestCase
{
    public function testItInstanciateWithoutConfiguration()
    {
        $server = new WebSocketServer(1000);
    }

    public function testItInstanciateWithConfiguration()
    {
        $server = new WebSocketServer(1000, '127.0.0.1', [
            'prod' => false
        ]);
    }

    public function testItThrowErrorOnWrongMessageHandlerInConfiguration()
    {
        $this->expectException(RuntimeException::class);

        $server = new WebSocketServer(1000, '127.0.0.1', [
            'messageHandlers' => [new class() {}]
        ]);
    }
}
