<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Http;

use Nekland\Tools\StringTools;

/**
 * Class Url
 *
 * Represent a WebSocket URL
 *
 * @internal
 */
class Url
{
    /**
     * @var bool
     */
    private $secured;

    /**
     * example: 127.0.0.1
     *
     * @var string
     */
    private $host;

    /**
     * example: 8080
     *
     * @var int
     */
    private $port;

    /**
     * example: /chat
     *
     * @var string
     */
    private $uri;

    public function __construct(string $url = null)
    {
        if (null !== $url) {
            $this->initialize($url);
        }
    }

    /**
     * @return bool
     */
    public function isSecured(): bool
    {
        return $this->secured;
    }

    /**
     * @param bool $secured
     * @return Url
     */
    public function setSecured(bool $secured = true)
    {
        $this->secured = $secured;

        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return Url
     */
    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return Url
     */
    public function setPort(int $port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     * @return Url
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Fill the object with given URL as string.
     *
     * @param string $url
     */
    private function initialize(string $url)
    {
        $match = preg_match('/^wss?:\/\/(.+):([\d]{2,5})(\/.+)?/', $url, $matches);

        if ($match !== 1 || !in_array(count($matches), [3, 4])) {
            throw new \InvalidArgumentException(sprintf('The URL %s is invalid.', $url));
        }

        $this->secured = StringTools::startsWith($url, 'wss');
        $this->host = $matches[1];
        $this->port = $matches[2];
        $this->uri = isset($matches[3]) ? $matches[3] : '/';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $str = $this->secured ? 'wss://' : 'ws://';
        $str .= $this->host . ':' . $this->port . $this->uri;

        return $str;
    }
}
