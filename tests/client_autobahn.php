<?php

require '../vendor/autoload.php';

const AGENT = 'Woketo/2.x';

use Nekland\Woketo\Core\AbstractConnection;

$numberOfTests = null;
class TestMessageHandler extends \Nekland\Woketo\Message\SimpleMessageHandler
{
    public function onConnection(AbstractConnection $connection)
    {
        echo ".";
    }

    public function onMessage(string $data, AbstractConnection $connection)
    {
        $connection->write($data);
    }

    public function onBinary(string $data, AbstractConnection $connection)
    {
        $connection->write($data);
    }
}

$client = new \Nekland\Woketo\Client\WebSocketClient( 'ws://127.0.0.1:9001/getCaseCount', ['prod' => false]);

$client->start(new class extends \Nekland\Woketo\Message\TextMessageHandler {
    public function onConnection(AbstractConnection $connection)
    {
        echo 'Starting test suite' . "\n";
    }

    public function onMessage(string $data, AbstractConnection $connection)
    {
        echo "Will perform $data tests\n";
        global $numberOfTests;
        $numberOfTests = (int) $data;
    }
});

for ($i = 1; $i <= $numberOfTests; $i++) {
    $client = new \Nekland\Woketo\Client\WebSocketClient('ws://127.0.0.1:9001/runCase?case=' . $i . '&agent=' . AGENT, ['prod' => false]);
    $client->start(new TestMessageHandler());
}

(new \Nekland\Woketo\Client\WebSocketClient('ws://127.0.0.1:9001/updateReports?agent=' . AGENT, ['prod' => false]))->start(new class extends \Nekland\Woketo\Message\TextMessageHandler {
    public function onConnection(AbstractConnection $connection)
    {
        echo "\nDONE!\n";
    }

    public function onMessage(string $data, AbstractConnection $connection)
    {
    }
});
