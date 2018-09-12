<?php

use Nekland\Woketo\Server\WebSocketServer;

require __DIR__ . '/../vendor/autoload.php';

$foo = new WebSocketServer(9001, '127.0.0.1', [
    'frame' => ['maxPayloadSize' => 16777216],
    'message' => ['maxMessagesBuffering' => 1000000],
    'prod' => false,
]);


class EchoServer implements \Nekland\Woketo\Message\MessageHandlerInterface
{
    public function onConnection(\Nekland\Woketo\Core\AbstractConnection $connection)
    {
    }

    public function onMessage(string $data, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        $connection->write($data);
    }

    public function onBinary(string $data, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        $connection->write($data, \Nekland\Woketo\Rfc6455\Frame::OP_BINARY);
    }

    public function onError(\Nekland\Woketo\Exception\WebsocketException $e, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        echo '(' . get_class($e) . ') ' . $e->getMessage() . "\n";
    }

    public function onDisconnect(\Nekland\Woketo\Core\AbstractConnection $connection)
    {
    }
}

$foo->setMessageHandler(new EchoServer());


$foo->start();

