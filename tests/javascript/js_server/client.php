<?php

require __DIR__ . '/../../../vendor/autoload.php';

use Nekland\Woketo\Core\AbstractConnection;

$client = new \Nekland\Woketo\Client\WebSocketClient( 'ws://127.0.0.1:8080/', ['prod' => false]);

$client->start(new class implements \Nekland\Woketo\Message\MessageHandlerInterface {
    public function onConnection(AbstractConnection $connection)
    {
        $connection->write('hello');
    }

    public function onMessage(string $data, AbstractConnection $connection)
    {
        echo 'Data receive: ' . $data . "\n";

        if ($data === 'world') {
            $connection->close();
        }
    }

    public function onBinary(string $data, AbstractConnection $connection) {}

    public function onError(\Nekland\Woketo\Exception\WebsocketException $e, AbstractConnection $connection)
    {
        var_dump($e->getMessage());
        echo $e->getTraceAsString();
    }

    public function onDisconnect(AbstractConnection $connection)
    {
        $connection->write('see you soon');
    }
});
