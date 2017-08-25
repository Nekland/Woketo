<?php

require __DIR__ . '/../vendor/autoload.php';

/**
 * Usage:
 *
 * `php client_autobahn.php`                 will run the complete autobahn client suite.
 * `php client_autobahn.php 1.1.1,1.1.2`     will run the 2 specified tests.
 */


use Nekland\Woketo\Client\WebSocketClient;
use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Meta;
use Nekland\Woketo\Rfc6455\Frame;

const AGENT = 'Woketo/' . Meta::VERSION;

$numberOfTests = null;
$tests = [];
if (!empty($argv[1])) {
    $tests = explode(',', $argv[1]);
}
$clientConfiguration = [
    'prod' => false,
    'frame' => ['maxPayloadSize' => 16777216],
    'message' => ['maxMessagesBuffering' => 1000000],
];

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
        $connection->write($data, Frame::OP_BINARY);
    }
}

if (empty($tests)) {
    $client = new WebSocketClient('ws://127.0.0.1:9001/getCaseCount', $clientConfiguration);

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
        $client = new \Nekland\Woketo\Client\WebSocketClient('ws://127.0.0.1:9001/runCase?case=' . $i . '&agent=' . AGENT, $clientConfiguration);
        $client->start(new TestMessageHandler());
    }


} else {
    echo "Running tests " . implode(', ', $tests) . "\n";
    foreach ($tests as $tuple) {
        (new WebSocketClient('ws://127.0.0.1:9001/runCase?casetuple=' . $tuple . '&agent=' . AGENT, $clientConfiguration))->start(new TestMessageHandler());
    }
}

(new \Nekland\Woketo\Client\WebSocketClient('ws://127.0.0.1:9001/updateReports?agent=' . AGENT, $clientConfiguration))->start(new class extends \Nekland\Woketo\Message\TextMessageHandler {
    public function onConnection(AbstractConnection $connection)
    {
        echo "\nDONE!\n";
    }

    public function onMessage(string $data, AbstractConnection $connection)
    {
    }
});
