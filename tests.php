<?php

use Nekland\Woketo\Server\Websocket;

require 'vendor/autoload.php';

$foo = new Websocket(8088);



class EchoServer implements \Nekland\Woketo\Message\MessageHandlerInterface
{
    public function onConnection(\Nekland\Woketo\Server\Connection $connection)
    {
    }

    public function onData($data, \Nekland\Woketo\Server\Connection $connection)
    {
        $connection->write($data);
    }
}

$foo->setMessageHandler(new EchoServer());




$foo->start();
