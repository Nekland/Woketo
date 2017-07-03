<?php

require '../vendor/autoload.php';


use Nekland\Woketo\Core\AbstractConnection;

$client = new \Nekland\Woketo\Client\WebSocketClient( 'ws://127.0.0.1:9001/getCaseCount', ['prod' => false]);

$client->start(new class extends \Nekland\Woketo\Message\TextMessageHandler {
    public function onConnection(AbstractConnection $connection)
    {
        echo 'Opened connection' . "\n";
    }

    public function onMessage(string $data, AbstractConnection $connection)
    {
        echo "Test $data\n";
    }
});
