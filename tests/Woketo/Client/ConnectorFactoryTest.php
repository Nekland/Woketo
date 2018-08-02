<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Client;


use Nekland\Woketo\Client\ConnectorFactory;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\Socket\TimeoutConnector;

class ConnectorFactoryTest extends TestCase
{
    public function testItBuildATimeoutConnector()
    {
        $loop = $this->prophesize(LoopInterface::class);
        $connectorFactory = new ConnectorFactory($loop->reveal());

        $this->assertInstanceOf(TimeoutConnector::class, $connectorFactory->createConnector());
    }
}
