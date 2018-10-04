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

abstract class SimpleMessageHandler implements MessageHandlerInterface
{
    public function onConnection(AbstractConnection $connection)
    {
        // Doing nothing
    }

    public function onError(WebsocketException $e, AbstractConnection $connection)
    {
        echo 'An error occurred : ' . $e->getMessage();
    }

    public function onDisconnect(AbstractConnection $connection)
    {
        // Doing nothing
    }
}
