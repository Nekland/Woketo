<?php

use Ratchet\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

class SimpleMessageServer implements \Ratchet\WebSocket\MessageComponentInterface {

    private $messages;

    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    public function onMessage(ConnectionInterface $from, \Ratchet\RFC6455\Messaging\MessageInterface $msg) {
        foreach ($this->messages as $message) {
            if ($message['message'] === $msg->getPayload()) {
                $from->send($message['response']);
            }
        }
    }
    public function onOpen(ConnectionInterface $conn) {
    }
    public function onClose(ConnectionInterface $conn) {
    }
    public function onError(ConnectionInterface $conn, \Exception $e) {
    }
}

$port = $argc > 1 ? $argv[1] : 9002;
$impl = sprintf('React\EventLoop\%sLoop', $argc > 2 ? $argv[2] : 'StreamSelect');

$loop = new $impl;
$sock = new React\Socket\Server('0.0.0.0:' . $port, $loop);

$wsServer = new Ratchet\WebSocket\WsServer(new SimpleMessageServer(json_decode(file_get_contents(__DIR__ . '/../data/messages.json'), true)));

$app = new Ratchet\Http\HttpServer($wsServer);
$server = new Ratchet\Server\IoServer($app, $sock, $loop);

$server->run();
