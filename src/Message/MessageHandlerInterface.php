<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
namespace Nekland\Woketo\Message;

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
     * Is called on new data.
     *
     * @param string $data
     * @param Connection $connection
     */
    public function onData($data, Connection $connection);
}
