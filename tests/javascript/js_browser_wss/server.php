<?php

require __DIR__ . '/../../../vendor/autoload.php';

$foo = new \Nekland\Woketo\Server\WebSocketServer(9001, '127.0.0.1', [
    'prod' => false,
    'ssl' => true,
    'certFile' => 'certificate/test.pem',
    'sslContextOptions' => [
        'verify_peer' => false,
        'allow_self_signed' => true
    ]
]);

class EchoServer implements \Nekland\Woketo\Message\MessageHandlerInterface
{
    public function onConnection(\Nekland\Woketo\Server\Connection $connection)
    {
    }

    public function onMessage(string $data, \Nekland\Woketo\Server\Connection $connection)
    {
        $connection->write($data);
    }

    public function onBinary(string $data, \Nekland\Woketo\Server\Connection $connection)
    {
        $connection->write($data, \Nekland\Woketo\Rfc6455\Frame::OP_BINARY);
    }

    public function onError(\Nekland\Woketo\Exception\WebsocketException $e, \Nekland\Woketo\Server\Connection $connection)
    {
        echo '(' . get_class($e) . ') ' . $e->getMessage() . "\n";
    }
}

$foo->setMessageHandler(new EchoServer());


$foo->start();
