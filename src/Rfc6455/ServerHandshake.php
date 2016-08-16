<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Rfc6455;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Exception\WebsocketVersionException;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;

/**
 * Class ServerHandshake
 *
 * This class is highly inspired by ratchetphp/RFC6455.
 */
class ServerHandshake
{
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const SUPPORTED_VERSIONS = [13];

    /**
     * Used when doing the handshake to encode the key, verifying client/server are speaking the same language
     * @param string   $key
     * @param Response $response
     * @return string
     */
    public function sign($key, Response $response = null)
    {
        if ($key instanceof Request) {
            $key = $key->getHeader('Sec-WebSocket-Key');
        }

        $sign = base64_encode(sha1($key . ServerHandshake::GUID, true));

        if (null !== $response) {
            $response->addHeader('Sec-WebSocket-Accept', $sign);
        }

        return $sign;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws WebsocketException
     */
    public function verify(Request $request)
    {
        if ($request->getHttpVersion() !== 'HTTP/1.1') {
            throw new WebsocketException(
                sprintf('Wrong http version, HTTP/1.1 expected, "%s" received.', $request->getHttpVersion())
            );
        }

        if ($request->getMethod() !== 'GET') {
            throw new WebsocketException(
                sprintf('Wrong http method, GET expected, "%" received.', $request->getMethod())
            );
        }

        $headers = $request->getHeaders();
        if (empty($headers['Sec-WebSocket-Key'])) {
            throw new WebsocketException(
                sprintf('Missing websocket key header.')
            );
        }

        if (empty($headers['Upgrade']) || 'websocket' !== strtolower($headers['Upgrade'])) {
            throw new WebsocketException(
                sprintf('Wrong or missing upgrade header.')
            );
        }
        
        $version = $headers->get('Sec-WebSocket-Version');
        if (!in_array($version, ServerHandshake::SUPPORTED_VERSIONS)) {
            throw new WebsocketVersionException(sprintf('Version %s not supported by Woketo for now.', $version));
        }

        return true;
    }
}
