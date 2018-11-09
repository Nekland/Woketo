<?php

use Ratchet\ConnectionInterface;

require_once __DIR__ . '/vendor/autoload.php';

class BinaryEcho implements \Ratchet\WebSocket\MessageComponentInterface {
    public function onMessage(ConnectionInterface $from, \Ratchet\RFC6455\Messaging\MessageInterface $msg) {
        $from->send($msg);
    }
    public function onOpen(ConnectionInterface $conn) {
    }
    public function onClose(ConnectionInterface $conn) {
    }
    public function onError(ConnectionInterface $conn, \Exception $e) {
    }
}

$port = $argc > 1 ? $argv[1] : 9003;
$impl = sprintf('React\EventLoop\%sLoop', $argc > 2 ? $argv[2] : 'StreamSelect');

$loop = new $impl;
$sock = new React\Socket\Server('0.0.0.0:' . $port, $loop);

$wsServer = new Ratchet\WebSocket\WsServer(new BinaryEcho);

$app = new Ratchet\Http\HttpServer($wsServer);
$server = new Ratchet\Server\IoServer($app, $sock, $loop);

$server->run();
