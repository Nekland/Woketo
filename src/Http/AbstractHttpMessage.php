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


use Nekland\Woketo\Exception\Http\HttpException;

/**
 * Class AbstractHttpMessage
 *
 * @internal
 */
abstract class AbstractHttpMessage
{
    /**
     * @var HttpHeadersBag
     */
    private $headers;

    /**
     * @var string for example "HTTP/1.1"
     */
    private $httpVersion;

    /**
     * @param string $httpVersion
     * @return self
     */
    protected function setHttpVersion($httpVersion)
    {
        $this->httpVersion = $httpVersion;

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     * @return self
     */
    public function addHeader(string $name, string $value)
    {
        if (null === $this->headers) {
            $this->headers = new HttpHeadersBag();
        }
        $this->headers->add($name, $value);

        return $this;
    }

    /**
     * @param string $header
     * @param mixed  $default
     * @return string
     */
    public function getHeader(string $header, $default = null)
    {
        return $this->headers[$header] ?: $default;
    }

    /**
     * @return string
     */
    public function getHttpVersion()
    {
        return $this->httpVersion;
    }

    /**
     * @return array|HttpHeadersBag
     */
    public function getHeaders()
    {
        return $this->headers ?: new HttpHeadersBag();
    }

    /**
     * @param string[]              $headers
     * @param AbstractHttpMessage   $request
     */
    protected static function initHeaders(array $headers, AbstractHttpMessage $request)
    {
        foreach ($headers as $header) {
            $cuttedHeader = \explode(':', $header);
            $request->addHeader(\trim($cuttedHeader[0]), trim(str_replace($cuttedHeader[0] . ':', '', $header)));
        }
    }

    /**
     * @param string $line
     * @return HttpException
     */
    protected static function createNotHttpException($line)
    {
        return new HttpException(
            \sprintf('The message is not an http request. "%s" received.', $line)
        );
    }
}
