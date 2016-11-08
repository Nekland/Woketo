<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
namespace Nekland\Woketo\Message;

use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Server\Connection;

/**
 * Interface MessageHandlerInterface
 *
 * If there is only one message handler object (that *you* instanciate) you can guess what is the current client using the spl hash of the connection.
 */
interface MessageHandlerInterface
{
    /**
     * Is called when a new connection is established.
     *
     * @param Connection $connection
     */
    public function onConnection(Connection $connection);

    /**
     * Is called on new text data.
     *
     * @param string     $data       Text data
     * @param Connection $connection
     */
    public function onMessage(string $data, Connection $connection);

    /**
     * Is called on new binary data.
     *
     * @param string     $data       Binary data
     * @param Connection $connection
     */
    public function onBinary(string $data, Connection $connection);

    /**
     * This callback is call when there is an error on the websocket protocol communication.
     *
     * @param WebsocketException $e
     */
    public function onError(WebsocketException $e, Connection $connection);
}
