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

    /**
     * @param MessageHandlerInterface $handler
     * @throws \Exception
     */
    public function start(MessageHandlerInterface $handler)
    {
        if ($this->config['prod'] && \extension_loaded('xdebug')) {
            throw new \Exception('xdebug is enabled, it\'s a performance issue. Disable that extension or specify "prod" option to false.');
        }

        $this->connection = new Connection(
            $this->url,
            $this->getConnectorFactory()->createConnector()->connect($this->url->getHost() . ':' . $this->url->getPort()),
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
            'prod' => true,
            'ssl' => [],
            'dns' => null,
        ], $config);

        return $this;
    }

    /**
     * Creates a connector factory with the given configuration if none given in the constructor.
     *
     * @return ConnectorFactoryInterface
     */
    private function getConnectorFactory() : ConnectorFactoryInterface
    {
        if ($this->connectorFactory !== null) {
            return $this->connectorFactory;
        }
        $this->connectorFactory = new ConnectorFactory();
        $this->connectorFactory->setLoop($this->getLoop());
        $this->connectorFactory->setSslOptions($this->config['ssl']);

        $this->connectorFactory->enableDns();
        if (null !== $this->config['dns']) {
            $this->connectorFactory->setDnsServer($this->config['dns']);
        }
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

    /**
     * @return MessageProcessor
     */
    private function getMessageProcessor(): MessageProcessor
    {
        if (!empty($this->messageProcessor)) {
            return $this->messageProcessor;
        }

        return $this->messageProcessor = new MessageProcessor();
    }
}
