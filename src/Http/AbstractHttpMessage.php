<?php
/**
 * This file is a part of a nekland library
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Http;


abstract class AbstractHttpMessage
{
    /**
     * @var string[]
     */
    private $headers = [];

    /**
     * @var string for example "HTTP/1.1"
     */
    private $httpVersion;

    /**
     * @param string $httpVersion
     * @return Request
     */
    protected function setHttpVersion($httpVersion)
    {
        $this->httpVersion = $httpVersion;

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return Request
     */
    public function addHeader(string $name, string $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    /**
     * @return array|\string[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
