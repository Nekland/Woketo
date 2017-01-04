<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Client;


use React\SocketClient\ConnectorInterface;

class ConnectorFactory implements ConnectorFactoryInterface
{
    public function createConnector(string $host, int $port, bool $secured = false, array $sslConfig = [])
    {
        // TODO: Implement createConnector() method.
    }
}