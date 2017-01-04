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

use React\Promise\PromiseInterface;

interface ConnectorFactoryInterface
{
    /**
     * @param string $host
     * @param int    $port
     * @param bool   $secured
     * @param array  $sslConfig
     * @return PromiseInterface
     */
    public function createConnector(string $host, int $port, bool $secured = false, array $sslConfig = []);
}