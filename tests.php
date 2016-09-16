<?php

use Nekland\Woketo\Server\Websocket;

require 'vendor/autoload.php';

$foo = new Websocket(9001);



class EchoServer implements \Nekland\Woketo\Message\MessageHandlerInterface
{
    public function onConnection(\Nekland\Woketo\Server\Connection $connection)
    {
    }

    public function onData($data, \Nekland\Woketo\Server\Connection $connection)
    {
        $connection->write($data);
    }

    public function onError(\Nekland\Woketo\Exception\WebsocketException $e, \Nekland\Woketo\Server\Connection $connection)
    {
        echo $e->getMessage() . "\n";
    }
}

$foo->setMessageHandler(new EchoServer());


$foo->start();

