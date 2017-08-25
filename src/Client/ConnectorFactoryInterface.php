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

use React\Socket\ConnectorInterface;

interface ConnectorFactoryInterface
{
    /**
     * @return ConnectorInterface
     */
    public function createConnector(): ConnectorInterface;
}
