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

use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\MessageProcessor;

class WebSocketClient
{
    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $host;

    /**
     * @var array
     */
    private $config;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ConnectorFactoryInterface
     */
    private $connectorFactory;

    public function __construct(int $port, string $host, array $config = [], ConnectorFactoryInterface $connectorFactory = null)
    {
        $this->port = $port;
        $this->host = $host;
        $this->connectorFactory = $connectorFactory ?: new ConnectorFactory();
        $this->setConfig($config);
    }

    public function start(MessageHandlerInterface $handler)
    {
        if ($this->config['prod'] && \extension_loaded('xdebug')) {
            throw new \Exception('xdebug is enabled, it\'s a performance issue. Disable that extension or specify "prod" option to false.');
        }

        $this->connection = new Connection(
            $this->port,
            $this->host,
            $this->connectorFactory->createConnector($this->host, $this->port),
            $this->getMessageProcessor(),
            $handler
        );
    }

    /**
     * @param array $config
     * @return self
     */
    public function setConfig(array $config = [])
    {
        $this->config = array_merge([
            'prod' => true
        ], $config);

        return $this;
    }

    private function getMessageProcessor()
    {
        if (!empty($this->messageProcessor)) {
            return $this->messageProcessor;
        }

        return $this->messageProcessor = new MessageProcessor();
    }
}
