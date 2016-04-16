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
     * Websocket constructor.
     *
     * @param int    $port    The number of the port to bind
     * @param string $address The address to listen on (by default 127.0.0.1)
     */
    public function __construct($port, $address = '127.0.0.1')
    {
        set_time_limit(0);

        if (false === $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            throw new SocketException('Impossible to create a new socket', $this->socket);
        }

        if (false === socket_bind($this->socket, $address, $port)) {
            throw new SocketException('Impossible to bind the socket', $this->socket);
        }

        if (false === socket_listen($this->socket, 5)) {
            throw new SocketException('Impossible to listen on ' . $address . ':' . $port, $this->socket);
        }

        $this->port = $port;
    }

    public function start()
    {
        $loop = new Loop($this);

        return $loop->start();
    }
}
