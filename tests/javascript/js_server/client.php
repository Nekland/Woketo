<?php

require __DIR__ . '/../../../vendor/autoload.php';

use Nekland\Woketo\Core\AbstractConnection;

$client = new \Nekland\Woketo\Client\WebSocketClient(8080, '127.0.0.1');

$client->start(new class implements \Nekland\Woketo\Message\MessageHandlerInterface {
    public function onConnection(AbstractConnection $connection)
    {
        $connection->write('hello');
    }

    public function onMessage(string $data, AbstractConnection $connection)
    {
        // TODO: Implement onMessage() method.
    }

    public function onBinary(string $data, AbstractConnection $connection)
    {
        // TODO: Implement onBinary() method.
    }

    public function onError(\Nekland\Woketo\Exception\WebsocketException $e, AbstractConnection $connection)
    {
        var_dump('error', $e->getMessage());
    }
});
