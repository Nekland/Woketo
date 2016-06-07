<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Server;

use Nekland\Woketo\Exception\SocketException;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\ServerHandshake;
use React\Socket\ConnectionInterface;

class Websocket
{
    /**
     * @var resource Socket of the server
     */
    private $socket;

    /**
     * @var int Store the port for debug purpose.
     */
    private $port;

    /**
     * @var string
     */
    private $address;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ServerHandshake
     */
    private $handshake;

    /**
     * @var MessageHandlerInterface
     */
    private $messageHandler;

    /**
     * tmp var for test purpose
     * @var Message
     */
    private $message;

    /**
     * Websocket constructor.
     *
     * @param int    $port    The number of the port to bind
     * @param string $address The address to listen on (by default 127.0.0.1)
     */
    public function __construct($port, $address = '127.0.0.1')
    {
        $this->address = $address;
        $this->port = $port;
        $this->handshake = new ServerHandshake();
    }

    public function setMessageHandler($messageHandler)
    {
        if (!$messageHandler instanceof MessageHandlerInterface &&  !is_string($messageHandler)) {
            throw new \InvalidArgumentException('The message handler must be an instance of MessageHandlerInterface or a string.');
        }
        if (is_string($messageHandler)) {
            try {
                $reflection = new \ReflectionClass($messageHandler);
                if(!$reflection->implementsInterface('Nekland\Woketo\Message\MessageHandlerInterface')) {
                    throw new \InvalidArgumentException('The messageHandler must implement MessageHandlerInterface');
                }
            } catch (\ReflectionException $e) {
                throw new \InvalidArgumentException('The messageHandler must be a string representing a class.');
            }
        }
        $this->messageHandler = $messageHandler;
    }

    public function start()
    {
        $this->message = new Message();
        $loop = \React\EventLoop\Factory::create();

        $socket = new \React\Socket\Server($loop);
        $socket->on('connection', [$this, 'newConnection']);
        $socket->listen($this->port);

        $loop->run();
    }

    public function newConnection(ConnectionInterface $socketStream)
    {
        $messageHandler = $this->messageHandler;
        if (is_string($messageHandler)) {
            $messageHandler = new $messageHandler;
        }
        
        $this->connections[] = new Connection($socketStream, $messageHandler);
    }
}
