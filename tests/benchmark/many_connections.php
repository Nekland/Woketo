<?php


require_once __DIR__ . '/../../vendor/autoload.php';

class DirectClose implements \Nekland\Woketo\Message\MessageHandlerInterface
{
    public function onConnection(\Nekland\Woketo\Core\AbstractConnection $connection)
    {
        // This method is called when a new client is connected to your server
        $connection->close();
    }

    public function onMessage(string $data, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        // This method is called when a text message is sent
    }

    public function onBinary(string $data, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        // This method is called when a binary message is sent
    }

    public function onError(\Nekland\Woketo\Exception\WebsocketException $e, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        // This method is called when an error occurs
    }

    public function onDisconnect(\Nekland\Woketo\Core\AbstractConnection $connection) {}
}


$times = 10000;

function help($times) {
    echo "This script connect to the server and then disconnect. It tries it $times times.\n";
    echo "Usage: php many_connections.php [test]\n\n";
    echo "[test] referes to a string that correspond to the script you run in this folder:\n";
    echo "- \"woketo\" if you run the script woketo/echo_server.php (port 9001)\n";
    echo "- \"ratchet\" if you run the script ratchet/echo_server.php (port 9002)\n";
    echo "- \"node_ws\" if you run the script node_ws/echo_server.js (port 9003)\n";

    exit();
}

require_once __DIR__.'/inc/init.php';

$client = new \Nekland\Woketo\Client\WebSocketClient('ws://127.0.0.1:'.$port, []);

$start = microtime(true);
for ($i = 0; $i < $times; $i++) {
    $client->start(new DirectClose());
}

echo "\nTime that $tool took to handle $times connections:\n" . (microtime(true) - $start) . "s\n";
