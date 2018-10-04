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

use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Exception\WebsocketException;

/**
 * Interface MessageHandlerInterface
 *
 * If there is only one message handler object (that *you* instantiate) you can guess what is the current client using the spl hash of the connection.
 */
interface MessageHandlerInterface
{
    /**
     * Is called when a new connection is established.
     *
     * @param AbstractConnection $connection
     */
    public function onConnection(AbstractConnection $connection);

    /**
     * Is called on new text data.
     *
     * @param string     $data       Text data
     * @param AbstractConnection $connection
     */
    public function onMessage(string $data, AbstractConnection $connection);

    /**
     * Is called on new binary data.
     *
     * @param string     $data       Binary data
     * @param AbstractConnection $connection
     */
    public function onBinary(string $data, AbstractConnection $connection);

    /**
     * This callback is call when there is an error on the websocket protocol communication.
     *
     * @param WebsocketException $e
     * @param AbstractConnection $connection
     */
    public function onError(WebsocketException $e, AbstractConnection $connection);

    /**
     * Is called when the connection is closed by the client
     *
     * @param AbstractConnection $connection
     */
    public function onDisconnect(AbstractConnection $connection);
}
