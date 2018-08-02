<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Client;


use Nekland\Woketo\Client\WebSocketClient;
use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Message\TextMessageHandler;
use PHPUnit\Framework\TestCase;

class WebSocketClientTest extends TestCase
{
    public function testItInstanciateWithoutConfiguration()
    {
        $client = new WebSocketClient('ws://localhost:8000');
        $this->assertInstanceOf(WebSocketClient::class, $client);
    }

    public function testItCannotStartWithXdebug()
    {
        if (!extension_loaded('xdebug')) {
            return;
        }
        $this->expectException(\Exception::class);
        $client = new WebSocketClient('ws://localhost:8000');
        $client->start(new class() extends TextMessageHandler {
            public function onMessage(string $data, AbstractConnection $connection) {
            }
        });
    }
}
