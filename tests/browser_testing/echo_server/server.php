<?php

require '../../../vendor/autoload.php';

use Nekland\Woketo\Server\Connection;
use Nekland\Woketo\Message\TextMessageHandler;
use Nekland\Woketo\Server\WebSocketServer;
use Nekland\Woketo\Core\AbstractConnection;

class EchoMessageHandler extends TextMessageHandler
{
    public function onConnection(AbstractConnection $connection)
    {
        echo "New client connected !\n";
    }

    public function onMessage(string $data, AbstractConnection $connection)
    {
        // Sending back the received data
        $connection->write($data);
    }


    public function onDisconnect(AbstractConnection $connection)
    {
        echo "Client disconnected !\n";
    }
}

$server = new WebSocketServer(1337, '127.0.0.1', [
    'prod' => false,
]);
$server->setMessageHandler(new EchoMessageHandler(), '/foo');
$server->start();
