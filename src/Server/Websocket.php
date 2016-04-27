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
use Nekland\Woketo\Rfc6455\ServerHandshake;

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

    public function start()
    {
        $loop = \React\EventLoop\Factory::create();

        $socket = new \React\Socket\Server($loop);
        $socket->on('connection', function ($conn) {
//            $conn->write("Hello there!\n");
//            $conn->write("Welcome to this amazing server!\n");
//            $conn->write("Here's a tip: don't say anything.\n");
//
//            $conn->on('data', function ($data) use ($conn) {
//                $conn->close();
//            });
            $conn->on('data', function ($data) use ($conn) {
                echo $data . "\n\n";
                if (null === $this->request) {
                    $this->request = Request::create($data);
                    $this->handshake->verify($this->request);
                    $response = new Response();
                    $response->setHttpResponse(Response::SWITCHING_PROTOCOLS);
                    $this->handshake->sign($response);
                    $response->send($conn);
                }
            });
        });
        $socket->listen($this->port);

        $loop->run();
    }
}
