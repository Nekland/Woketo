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
use Nekland\Woketo\Http\Request;

/**
 * Class ServerHandshake
 *
 * This class is highly inspired by ratchetphp/RFC6455.
 */
class ServerHandshake
{
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * Used when doing the handshake to encode the key, verifying client/server are speaking the same language
     * @param  string $key
     * @return string
     * @internal
     */
    public function sign($key)
    {
        return base64_encode(sha1($key . ServerHandshake::GUID, true));
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

        if (empty($headers['Upgrade']) || 'websocket' !== $headers['Upgrade']) {
            throw new WebsocketException(
                sprintf('Wrong or missing upgrade header.')
            );
        }

        return true;
    }
}
