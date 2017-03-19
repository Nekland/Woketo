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

use React\EventLoop\Factory as LoopFactory;
use React\Dns\Resolver\Factory as DnsResolverFactory;
use React\EventLoop\LoopInterface;
use React\SocketClient\DnsConnector;
use React\SocketClient\SecureConnector;
use React\SocketClient\TcpConnector;
use React\SocketClient\TimeoutConnector;

class ConnectorFactory implements ConnectorFactoryInterface
{
    /**
     * @var string
     */
    private $dnsServer;

    /**
     * @var bool
     */
    private $dnsEnabled;

    /**
     * @var bool
     */
    private $sslEnabled;

    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct()
    {
        $this->dnsEnabled = false;
        $this->sslEnabled = false;
        $this->dnsServer = '8.8.8.8'; // Google DNS
    }

    /**
     * @return ConnectorFactory
     */
    public function enableDns() : ConnectorFactory
    {
        $this->dnsEnabled = true;

        return $this;
    }

    /**
     * @return ConnectorFactory
     */
    public function disableDns() : ConnectorFactory
    {
        $this->dnsEnabled = false;

        return $this;
    }

    /**
     * @return ConnectorFactory
     */
    public function enableSsl() : ConnectorFactory
    {
        $this->sslEnabled = true;

        return $this;
    }

    /**
     * @return ConnectorFactory
     */
    public function disableSsl() : ConnectorFactory
    {
        $this->sslEnabled = true;

        return $this;
    }

    /**
     * @param string $server    Ip address of the DNS server you want to contact.
     * @return ConnectorFactory
     */
    public function setDnsServer(string $server) : ConnectorFactory
    {
        $this->dnsServer = $server;

        return $this;
    }

    /**
     * @param LoopInterface $loop
     * @return ConnectorFactory
     */
    public function setLoop(LoopInterface $loop) : ConnectorFactory
    {
        $this->loop = $loop;

        return $this;
    }

    public function createConnector(string $host, int $port, bool $secured = false, array $sslConfig = [])
    {
        $connector = new TcpConnector($this->loop);

        if ($this->dnsEnabled) {
            $resolver = (new DnsResolverFactory())->create($this->dnsServer, $this->loop);
            $connector = new DnsConnector($connector, $resolver);
        }

        if ($this->sslEnabled) {
            $connector = new SecureConnector($connector, $this->loop, $sslConfig);
        }

        $connector = new TimeoutConnector($connector, 3.0, $this->loop);

        return $connector->connect($host . ':' . $port);
    }
}
