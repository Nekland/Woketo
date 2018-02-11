<?php

declare(strict_types = 1);
require_once __DIR__ . '/../../vendor/autoload.php';

class SendMessagesAndWatchResponse implements \Nekland\Woketo\Message\MessageHandlerInterface
{
    private $errors;
    private $expectedResponses;
    private $times;
    private $messages;

    public function __construct(int $times, $messages)
    {
        $this->errors = 0;
        $this->expectedResponses = [];
        $this->times = $times;
        $this->messages = $messages;
    }

    public function onConnection(\Nekland\Woketo\Core\AbstractConnection $connection)
    {
        // This method is called when a new client is connected to your server

        for ($i = 0; $i < $this->times; $i++) {
            foreach ($this->messages as $message) {
                $this->expectedResponses[] = $message['response'];
                $connection->write($message['message']);
            }
        }

        $connection->close();
    }

    public function onMessage(string $data, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        if (reset($this->expectedResponses) !== $data) {
            $this->errors++;
            if (in_array($data, $this->expectedResponses)) {
                unset($this->expectedResponses[array_search($data, $this->expectedResponses)]);
            } else {
                echo "\n> A weirdo error occurred\n";
            }
        } else {
            array_shift($this->expectedResponses);
        }
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

    public function getNbOfErrors()
    {
        return $this->errors;
    }
}


$times = 10000;
$data = json_decode(file_get_contents(__DIR__ . '/data/messages.json'), true);

function help($times) {
    echo "This script connect to the server and send messages based on messages.json file. It tries it $times times.\n";
    echo "Usage: php simple_messages.php [test]\n\n";
    echo "[test] referes to a string that correspond to the script you run in this folder:\n";
    echo "- \"woketo\" if you run the script woketo/simple_messages.php (port 9001)\n";
    echo "- \"ratchet\" if you run the script ratchet/simple_messages.php (port 9002)\n";
    echo "- \"node_ws\" if you run the script node_ws/simple_messages.js (port 9003)\n";

    exit();
}

require_once __DIR__.'/inc/init.php';

$start = microtime(true);

$client = new \Nekland\Woketo\Client\WebSocketClient('ws://127.0.0.1:' . $port, []);
$client->start(new SendMessagesAndWatchResponse($times, $data));


echo "\nTime that $tool took to handle $times the messages list:\n" . (microtime(true) - $start) . "s\n";
