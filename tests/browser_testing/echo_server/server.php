<?php

require '../../../vendor/autoload.php';

use Nekland\Woketo\Server\Connection;
use Nekland\Woketo\Message\TextMessageHandler;
use Nekland\Woketo\Server\WebSocketServer;

class EchoMessageHandler extends TextMessageHandler
{
    public function onConnection(Connection $connection)
    {
        echo "New client connected !\n";
    }

    public function onMessage(string $data, Connection $connection)
    {
        // Sending back the received data
        $connection->write($data);
    }
}

$server = new WebSocketServer(1337, '127.0.0.1', [
    'prod' => false,
]);
$server->setMessageHandler(new EchoMessageHandler(), '/foo');
$server->start();
