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

abstract class SimpleMessageHandler implements MessageHandlerInterface
{
    public function onConnection(Connection $connection)
    {
        // Doing nothing
    }

    public function onError(WebsocketException $e, Connection $connection)
    {
        echo 'An error occurred : ' . $e->getMessage();
    }
}
