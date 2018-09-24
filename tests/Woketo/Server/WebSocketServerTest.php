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


use Evenement\EventEmitterTrait;
use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Exception\ConfigException;
use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Message\TextMessageHandler;
use Nekland\Woketo\Server\WebSocketServer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use React\Stream\WritableStreamInterface;

class WebSocketServerTest extends TestCase
{
    public function testItInstanciateWithoutConfiguration()
    {
        $server = new WebSocketServer(1000);
        $this->assertInstanceOf(WebSocketServer::class, $server);
    }

    public function testItInstanciateWithConfiguration()
    {
        $server = new WebSocketServer(1000, '127.0.0.1', [
            'prod' => false
        ]);
        $this->assertInstanceOf(WebSocketServer::class, $server);
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

    public function testItCallTheConnectionMethodOfHandler()
    {
        $handler = new class extends TextMessageHandler {
            public $called = false;
            public function onMessage(string $data, AbstractConnection $connection) {}

            public function onConnection(AbstractConnection $connection)
            {
                $this->called = true;
            }
        };

        $server = new WebSocketServer(1000, '127.0.0.1', ['prod' => false]);
        $server->setMessageHandler($handler);
        $server->setLoop($this->prophesize(LoopInterface::class)->reveal());
        $server->setSocketServer($socket = new FakeSocketServerForTestMethodHandlerConnection());
        $server->setLogger(new NullLogger());
        $server->start();
        $socket->callCb($co = new ServerReactConnectionMock());

        $co->emit('data', [self::getHandshake()]);
        $this->assertTrue($handler->called);
    }

    public function testItCallTheDisconnectionMethodOfHandler()
    {
        $handler = new class extends TextMessageHandler {
            public $called = false;
            public function onMessage(string $data, AbstractConnection $connection) {}
            public function onConnection(AbstractConnection $connection) {}

            public function onDisconnect(AbstractConnection $connection)
            {
                $this->called = true;
            }
        };

        $server = new WebSocketServer(1000, '127.0.0.1', ['prod' => false]);
        $server->setMessageHandler($handler);
        $server->setLoop($this->prophesize(LoopInterface::class)->reveal());
        $server->setSocketServer($socket = new FakeSocketServerForTestMethodHandlerConnection());
        $server->setLogger(new NullLogger());
        $server->start();
        $socket->callCb($co = new ServerReactConnectionMock());
        $co->emit('data', [self::getHandshake()]);
        $co->emit('end');
        $this->assertTrue($handler->called);
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

    public static function getHandshake()
    {
        return "GET /foo HTTP/1.1\r\n"
            . "Host: 127.0.0.1:8088\r\n"
            . "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:45.0) Gecko/20100101 Firefox/45.0\r\n"
            . "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
            . "Accept-Language: en-US,en;q=0.5\r\n"
            . "Accept-Encoding: gzip, deflate\r\n"
            . "Sec-WebSocket-Version: 13\r\n"
            . "Origin: null\r\n"
            . "Sec-WebSocket-Extensions: permessage-deflate\r\n"
            . "Sec-WebSocket-Key: nm7Ml8Q7dGJGWWdqnfM7AQ==\r\n"
            . "Connection: keep-alive, Upgrade\r\n"
            . "Pragma: no-cache\r\n"
            . "Cache-Control: no-cache\r\n"
            . "Upgrade: websocket\r\n\r\n";
    }
}

/**
 * Mock for react php
 */
class FakeServerAndConnection implements ServerInterface, ConnectionInterface {
    private $onData;
    private $onConnect;
    public function __construct()
    {
    }

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
    public function listeners($event = null){}
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

    public function getLocalAddress(){}
    public function getAddress(){}
}

/**
 * Mock for react php
 */
class FakeLoop implements LoopInterface
{
    public function addReadStream($stream, $listener) {}
    public function addWriteStream($stream, $listener) {}
    public function removeReadStream($stream) {}
    public function removeWriteStream($stream) {}
    public function removeStream($stream) {}
    public function addTimer($interval, $callback) {}
    public function addPeriodicTimer($interval, $callback) {}
    public function cancelTimer(TimerInterface $timer) {}
    public function isTimerActive(TimerInterface $timer) {}
    public function nextTick($listener) {}
    public function futureTick($listener) {}
    public function run() {}
    public function stop() {}
    public function addSignal($signal, $listener) {}
    public function removeSignal($signal, $listener) {}
}


class FakeSocketServerForTestMethodHandlerConnection implements ServerInterface
{
    private $cb;
    public function callCb(ConnectionInterface $connection)
    {
        $cb = $this->cb;
        $cb($connection);
    }
    public function on($event, callable $listener) {
        $this->cb = $listener;
    }
    public function once($event, callable $listener) {}
    public function removeListener($event, callable $listener) {}
    public function removeAllListeners($event = null) {}
    public function listeners($event = null) {}
    public function emit($event, array $arguments = []) {}
    public function getAddress() {}
    public function pause() {}
    public function resume() {}
    public function close() {}
}


class ServerReactConnectionMock implements ConnectionInterface
{
    use EventEmitterTrait;

    public function getRemoteAddress() {}

    public function isReadable(){}

    public function pause() {}

    public function resume() {}

    public function pipe(WritableStreamInterface $dest, array $options = array()) {}

    public function close() {}

    public function isWritable() {}

    public function write($data) {}

    public function end($data = null) {}

    public function getLocalAddress(){}
}
