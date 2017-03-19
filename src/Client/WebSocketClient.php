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

use Nekland\Woketo\Http\Url;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;

class WebSocketClient
{
    /**
     * @var Url
     */
    private $url;

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

    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct(string $url, array $config = [], ConnectorFactoryInterface $connectorFactory = null)
    {
        $this->url = new Url($url);
        $this->connectorFactory = $connectorFactory;
        $this->setConfig($config);
    }

    public function start(MessageHandlerInterface $handler)
    {
        if ($this->config['prod'] && \extension_loaded('xdebug')) {
            throw new \Exception('xdebug is enabled, it\'s a performance issue. Disable that extension or specify "prod" option to false.');
        }

        $this->connection = new Connection(
            $this->url,
            $this->getConnectorFactory()->createConnector($this->url->getHost(), $this->url->getPort()),
            $this->getMessageProcessor(),
            $handler
        );

        $this->loop->run();
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

    /**
     * @return ConnectorFactory
     */
    private function getConnectorFactory() : ConnectorFactory
    {
        if ($this->connectorFactory === null) {
            $this->connectorFactory = new ConnectorFactory();
        }
        $this->connectorFactory->setLoop($this->getLoop());

        $this->connectorFactory->enableDns();
        if ($this->url->isSecured()) {
            $this->connectorFactory->enableSsl();
        }

        return $this->connectorFactory;
    }

    /**
     * @return LoopInterface
     */
    private function getLoop() : LoopInterface
    {
        if (null !== $this->loop) {
            return $this->loop;
        }

        return $this->loop = LoopFactory::create();
    }

    private function getMessageProcessor()
    {
        if (!empty($this->messageProcessor)) {
            return $this->messageProcessor;
        }

        return $this->messageProcessor = new MessageProcessor();
    }
}
