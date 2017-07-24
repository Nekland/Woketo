<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Rfc6455\Handshake;
use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Exception\WebsocketVersionException;
use Nekland\Woketo\Http\AbstractHttpMessage;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;


/**
 * Class ServerHandshake
 *
 * This class is highly inspired by ratchetphp/RFC6455.
 */
class ServerHandshake implements HandshakeInterface
{
    public function sign(AbstractHttpMessage $response = null, string $key = null)
    {
        $signature = \base64_encode(\sha1($key . HandshakeInterface::GUID, true));

        if (null !== $response) {
            $response->addHeader('Sec-WebSocket-Accept', $signature);
        }

        return $signature;
    }

    /**
     * https://tools.ietf.org/html/rfc6455#section-4.2
     * 
     * @param AbstractHttpMessage $request
     * @param string|null         $key
     * @return bool
     * @throws RuntimeException
     * @throws WebsocketException
     * @throws WebsocketVersionException
     */
    public function verify(AbstractHttpMessage $request, string $key = null)
    {
        if (!$request instanceof Request) {
            throw new RuntimeException(
                sprintf('The client handshake cannot verify something else than a Response object, %s given.', get_class($response))
            );
        }

        if ($request->getHttpVersion() !== 'HTTP/1.1') {
            throw new WebsocketException(
                \sprintf('Wrong http version, HTTP/1.1 expected, "%s" received.', $request->getHttpVersion())
            );
        }

        if ($request->getMethod() !== 'GET') {
            throw new WebsocketException(
                \sprintf('Wrong http method, GET expected, "%" received.', $request->getMethod())
            );
        }

        $headers = $request->getHeaders();
        if (empty($headers['Sec-WebSocket-Key'])) {
            throw new WebsocketException(
                \sprintf('Missing websocket key header.')
            );
        }

        if (empty($headers['Upgrade']) || 'websocket' !== \strtolower($headers['Upgrade'])) {
            throw new WebsocketException(
                \sprintf('Wrong or missing upgrade header.')
            );
        }

        $version = $headers->get('Sec-WebSocket-Version');
        if (!\in_array($version, ServerHandshake::SUPPORTED_VERSIONS)) {
            throw new WebsocketVersionException(sprintf('Version %s not supported by Woketo for now.', $version));
        }

        return true;
    }

    /**
     * @param Request $request
     * @return string
     * @throws WebsocketException
     */
    public function extractKeyFromRequest(Request $request)
    {
        $key = $request->getHeader('Sec-WebSocket-Key');

        if (empty($key)) {
            throw new WebsocketException('No key found in the request.');
        }

        return $key;
    }
}
