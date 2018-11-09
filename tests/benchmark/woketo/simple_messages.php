<?php

require __DIR__ . '/../../../vendor/autoload.php';

$foo = new \Nekland\Woketo\Server\WebSocketServer(9001, '127.0.0.1', [
    'prod' => true,
]);
class SimpleMessageServer implements \Nekland\Woketo\Message\MessageHandlerInterface
{
    private $messages;

    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    public function onConnection(\Nekland\Woketo\Core\AbstractConnection $connection) {}

    public function onDisconnect(\Nekland\Woketo\Core\AbstractConnection $connection) {}

    public function onMessage(string $data, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        foreach ($this->messages as $message) {
            if ($message['message'] === $data) {
                $connection->write($message['response']);
            }
        }
    }

    public function onBinary(string $data, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        // Should not occur ATM
    }

    public function onError(\Nekland\Woketo\Exception\WebsocketException $e, \Nekland\Woketo\Core\AbstractConnection $connection)
    {
        echo '(' . get_class($e) . ') ' . $e->getMessage() . "\n";
    }
}


$foo->setMessageHandler(new SimpleMessageServer(json_decode(file_get_contents(__DIR__ . '/../data/messages.json'), true)));

$foo->start();
