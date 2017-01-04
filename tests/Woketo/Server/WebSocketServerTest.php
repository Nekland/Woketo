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


use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Exception\ConfigException;
use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Message\TextMessageHandler;
use Nekland\Woketo\Server\Connection;
use Nekland\Woketo\Server\WebSocketServer;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use React\Stream\WritableStreamInterface;

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
            'prod' => false,
            'messageHandlers' => [new class() {}],
        ]);
    }

    public function testItThrowsErrorOnWrongSslConfig()
    {
        $this->expectException(ConfigException::class);

        $server = new WebSocketServer(1000, '127.0.0.1', [
            'ssl' => true, // Missing cert file
        ]);
    }

    /**
     * @dataProvider getMessageHandlerTestData
     */
    public function testItSupportHandlerWithUri($handler, $uri, $uriToGet, $print)
    {
        $server = new WebSocketServer(1000, '127.0.0.1', [
            'prod' => false,
        ]);
        $server->setMessageHandler($handler, $uri);

        $fakeSocketServer = new FakeServerAndConnection();
        $server->setLoop(new FakeLoop());
        $server->setSocketServer($fakeSocketServer);

        \ob_start();

        $server->start();
        $fakeSocketServer->connect();
        $fakeSocketServer->sendHandshake("GET $uriToGet HTTP/1.1\r\nHost: example.com:8000\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==\r\nSec-WebSocket-Version: 13");

        $content = \ob_get_clean();
        $this->assertContains($print, $content);

    }

    public function getMessageHandlerTestData()
    {
        return [
            [
                new class extends TextMessageHandler {
                    public function onConnection(AbstractConnection $connection)
                    {
                        echo 'foo';
                    }

                    public function onMessage(string $data, AbstractConnection $connection){}
                },
                '/foo',
                '/foo',
                'foo'
            ],
            [
                new class extends TextMessageHandler {
                    public function onConnection(AbstractConnection $connection)
                    {
                        echo 'bar';
                    }

                    public function onMessage(string $data, AbstractConnection $connection){}
                },
                '/bar',
                '/bar',
                'bar'
            ],

            // Wrong URI asked/handler matching
            [
                new class extends TextMessageHandler {
                    public function onConnection(AbstractConnection $connection)
                    {
                        echo 'bar';
                    }

                    public function onMessage(string $data, AbstractConnection $connection){}
                },
                '/bar',
                '/baz',
                'Connection closed.' // Log info message
            ]
        ];
    }
}

/**
 * Mock for react php
 */
class FakeServerAndConnection implements ServerInterface, ConnectionInterface {
    private $onData;
    private $onConnect;
    public function sendHandshake($handshake)
    {
        $onData = $this->onData;
        $onData($handshake);
    }

    public function connect()
    {
        $onConnect = $this->onConnect;
        $onConnect($this);
    }

    public function on($event, callable $listener){
        if ($event === 'connection') {
            $this->onConnect = $listener;
        }
        if ($event === 'data') {
            $this->onData = $listener;
        }
    }

    public function once($event, callable $listener){}
    public function removeListener($event, callable $listener){}
    public function removeAllListeners($event = null){}
    public function listeners($event){}
    public function emit($event, array $arguments = []){}
    public function listen($port, $host = '127.0.0.1'){}
    public function getPort(){}
    public function shutdown(){}
    public function getRemoteAddress(){}
    public function isReadable(){}
    public function pause(){}
    public function resume(){}
    public function pipe(WritableStreamInterface $dest, array $options = array()){}
    public function close(){}
    public function isWritable(){}
    public function write($data){}
    public function end($data = null){}
}

/**
 * Mock for react php
 */
class FakeLoop implements LoopInterface
{
    public function addReadStream($stream, callable $listener){}
    public function addWriteStream($stream, callable $listener){}
    public function removeReadStream($stream){}
    public function removeWriteStream($stream){}
    public function removeStream($stream){}
    public function addTimer($interval, callable $callback){}
    public function addPeriodicTimer($interval, callable $callback){}
    public function cancelTimer(TimerInterface $timer){}
    public function isTimerActive(TimerInterface $timer){}
    public function nextTick(callable $listener){}
    public function futureTick(callable $listener){}
    public function tick(){}
    public function run(){}
    public function stop(){}
}
