<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Server;

use Nekland\Woketo\Exception\ConfigException;
use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Handshake\ServerHandshake;
use Nekland\Woketo\Rfc6455\MessageFactory;
use Nekland\Woketo\Rfc6455\FrameHandler\CloseFrameHandler;
use Nekland\Woketo\Rfc6455\FrameHandler\RsvCheckFrameHandler;
use Nekland\Woketo\Rfc6455\FrameHandler\WrongOpcodeFrameHandler;
use Nekland\Woketo\Rfc6455\FrameHandler\PingFrameHandler;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\SimpleLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;

class WebSocketServer
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
     * @var ServerHandshake
     */
    private $handshake;

    /**
     * @var MessageHandlerInterface[]
     */
    private $messageHandlers;

    /**
     * @var Connection[]
     */
    private $connections;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * @var MessageProcessor
     */
    private $messageProcessor;

    /**
     * @var array
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param int    $port   The number of the port to bind
     * @param string $host   The host to listen on (by default 127.0.0.1)
     * @param array  $config
     */
    public function __construct($port, $host = '127.0.0.1', $config = [])
    {
        $this->setConfig($config);
        $this->host = $host;
        $this->port = $port;
        $this->handshake = new ServerHandshake();
        $this->connections = [];
        $this->buildMessageProcessor();

        // Some optimization
        \gc_enable();       // As the process never stops, the garbage collector will be usefull, you may need to call it manually sometimes for performance purpose
        \set_time_limit(0); // It's by default on most server for cli apps but better be sure of that fact
    }

    /**
     * @param MessageHandlerInterface|string $messageHandler An instance of a class as string
     * @param string                         $uri            The URI you want to bind on
     */
    public function setMessageHandler($messageHandler, $uri = '*')
    {
        if (!$messageHandler instanceof MessageHandlerInterface &&  !\is_string($messageHandler)) {
            throw new \InvalidArgumentException('The message handler must be an instance of MessageHandlerInterface or a string.');
        }
        if (\is_string($messageHandler)) {
            try {
                $reflection = new \ReflectionClass($messageHandler);
                if(!$reflection->implementsInterface('Nekland\Woketo\Message\MessageHandlerInterface')) {
                    throw new \InvalidArgumentException('The messageHandler must implement MessageHandlerInterface');
                }
            } catch (\ReflectionException $e) {
                throw new \InvalidArgumentException('The messageHandler must be a string representing a class.');
            }
        }
        $this->messageHandlers[$uri] = $messageHandler;
    }

    /**
     * Launch the WebSocket server and an infinite loop that act on event.
     *
     * @throws \Exception
     */
    public function start()
    {
        if ($this->config['prod'] && \extension_loaded('xdebug')) {
            throw new \Exception('xdebug is enabled, it\'s a performance issue. Disable that extension or specify "prod" option to false.');
        }

        $this->loop = $this->loop ?? \React\EventLoop\Factory::create();
        $this->server = $this->server ?? new \React\Socket\TcpServer($this->host . ':' . $this->port, $this->loop);

        if ($this->config['ssl']) {
            $this->server = new \React\Socket\SecureServer($this->server, $this->loop, array_merge([
                'local_cert' => $this->config['certFile'],
                'passphrase' => $this->config['passphrase'],
            ], $this->config['sslContextOptions']));
            $this->getLogger()->info('Enabled ssl');
        }

        $this->server->on('connection', function (ConnectionInterface $socketStream) {
            $this->onNewConnection($socketStream);
        });

        $this->getLogger()->info('Listening on ' . $this->host . ':' . $this->port);

        $this->loop->run();
    }

    /**
     * @param ConnectionInterface $socketStream
     */
    private function onNewConnection(ConnectionInterface $socketStream)
    {
        $connection = new Connection($socketStream, function ($uri, Connection $connection) {
            return $this->getMessageHandler($uri, $connection);
        }, $this->loop, $this->messageProcessor);

        $socketStream->on('end', function () use($connection) {
            $this->onDisconnect($connection);
        });

        $connection->setLogger($this->getLogger());
        $connection->getLogger()->info(sprintf('Ip "%s" establish connection', $connection->getIp()));
        $this->connections[] = $connection;
    }

    /**
     *
     * @param Connection $connection
     */
    private function onDisconnect(Connection $connection)
    {
        $this->removeConnection($connection);
        $connection->getLogger()->info(sprintf('Ip "%s" left connection', $connection->getIp()));
    }

    /**
     * Remove a Connection instance by his object id
     * @param Connection        $connection
     * @throws RuntimeException This method throw an exception if the $connection instance object isn't findable in websocket server's connections
     */
    private function removeConnection(Connection $connection)
    {
        $connectionId = spl_object_hash($connection);
        foreach ($this->connections as $index => $connectionItem) {
            if ($connectionId === spl_object_hash($connectionItem)) {
                unset($this->connections[$index]);
                return;
            }
        }

        $this->logger->critical('No connection found in the server connection list, impossible to delete the given connection id. Something wrong happened');
        throw new RuntimeException('No connection found in the server connection list, impossible to delete the given connection id. Something wrong happened');
    }

    /**
     * @param string $uri
     * @param Connection $connection
     * @return MessageHandlerInterface|null
     */
    private function getMessageHandler(string $uri, Connection $connection)
    {
        $handler = null;

        if (!empty($this->messageHandlers[$uri])) {
            $handler = $this->messageHandlers[$uri];
        }

        if (null === $handler && !empty($this->messageHandlers['*'])) {
            $handler = $this->messageHandlers['*'];
        }

        if (null !== $handler) {
            if (\is_string($handler)) {
                $handler = new $handler;
            }

            return $handler;
        }

        $this->logger->warning('Connection on ' . $uri . ' but no handler found.');
        return null;
    }

    /**
     * Build the message processor with configuration
     */
    private function buildMessageProcessor()
    {
        $this->messageProcessor = new MessageProcessor(
            false,
            new FrameFactory($this->config['frame']),
            new MessageFactory($this->config['message'])
        );
        $this->messageProcessor->addHandler(new PingFrameHandler());
        $this->messageProcessor->addHandler(new CloseFrameHandler());
        $this->messageProcessor->addHandler(new WrongOpcodeFrameHandler());
        $this->messageProcessor->addHandler(new RsvCheckFrameHandler());

        foreach ($this->config['messageHandlers'] as $handler) {
            if (!$handler instanceof MessageHandlerInterface) {
                throw new RuntimeException(sprintf('%s is not an instance of MessageHandlerInterface but must be !', get_class($handler)));
            }
        }
    }

    /**
     * Sets the configuration
     *
     * @param array $config
     * @throws ConfigException
     */
    private function setConfig(array $config)
    {
        $this->config = \array_merge([
            'frame' => [],
            'message' => [],
            'messageHandlers' => [],
            'prod' => true,
            'ssl' => false,
            'certFile' => '',
            'passphrase' => '',
            'sslContextOptions' => [],
        ], $config);

        if ($this->config['ssl'] && !is_file($this->config['certFile'])) {
            throw new ConfigException('With ssl configuration, you need to specify a certificate file.');
        }
    }

    /**
     * @return SimpleLogger|LoggerInterface
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            return $this->logger = new SimpleLogger(!$this->config['prod']);
        }

        return $this->logger;
    }

    /**
     * Allows you to set a custom logger
     *
     * @param LoggerInterface $logger
     * @return WebSocketServer
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Allows to specify a loop that will be used instead of the reactphp generated loop.
     *
     * @param LoopInterface $loop
     * @return WebSocketServer
     */
    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    /**
     * @param ServerInterface $server
     * @return WebSocketServer
     */
    public function setSocketServer(ServerInterface $server)
    {
        $this->server = $server;

        return $this;
    }
}
