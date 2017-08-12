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

use React\Dns\Resolver\Factory as DnsResolverFactory;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectorInterface;
use React\Socket\DnsConnector;
use React\Socket\SecureConnector;
use React\Socket\TcpConnector;
use React\Socket\TimeoutConnector;

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

    /**
     * @var array
     */
    private $sslOptions;

    public function __construct(LoopInterface $loop)
    {
        $this->dnsEnabled = false;
        $this->sslEnabled = false;
        $this->dnsServer = '8.8.8.8'; // Google DNS
        $this->sslOptions = [];
        $this->loop = $loop;
    }

    /**
     * @return ConnectorFactory
     */
    public function enableDns(): ConnectorFactory
    {
        $this->dnsEnabled = true;

        return $this;
    }

    /**
     * @return ConnectorFactory
     */
    public function disableDns(): ConnectorFactory
    {
        $this->dnsEnabled = false;

        return $this;
    }

    /**
     * @return ConnectorFactory
     */
    public function enableSsl(): ConnectorFactory
    {
        $this->sslEnabled = true;

        return $this;
    }

    /**
     * @return ConnectorFactory
     */
    public function disableSsl(): ConnectorFactory
    {
        $this->sslEnabled = false;

        return $this;
    }

    /**
     * @param string $server    Ip address of the DNS server you want to contact.
     * @return ConnectorFactory
     */
    public function setDnsServer(string $server): ConnectorFactory
    {
        $this->dnsServer = $server;

        return $this;
    }

    /**
     * This allows the user to use its own loop to potentially use it for something else.
     *
     * @param LoopInterface $loop
     * @return ConnectorFactoryInterface
     */
    public function setLoop(LoopInterface $loop): ConnectorFactoryInterface
    {
        $this->loop = $loop;

        return $this;
    }

    /**
     * @param array $options
     * @return ConnectorFactory
     */
    public function setSslOptions(array $options): ConnectorFactory
    {
        $this->sslOptions = $options;

        return $this;
    }

    public function createConnector(): ConnectorInterface
    {
        $connector = new TcpConnector($this->loop);

        if ($this->dnsEnabled) {
            $resolver = (new DnsResolverFactory())->create($this->dnsServer, $this->loop);
            $connector = new DnsConnector($connector, $resolver);
        }

        if ($this->sslEnabled) {
            $connector = new SecureConnector($connector, $this->loop, $this->sslOptions);
        }

        $connector = new TimeoutConnector($connector, 3.0, $this->loop);

        return $connector;
    }
}
