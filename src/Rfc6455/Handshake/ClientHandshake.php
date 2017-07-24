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
use Nekland\Woketo\Exception\UnsupportedException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Http\AbstractHttpMessage;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Http\Url;

class ClientHandshake implements HandshakeInterface
{
    const WEBSOCKET_VERSION = 13;
    
    /**
     * {@inheritdoc}
     */
    public function sign(AbstractHttpMessage $request, string $key = null)
    {
        if (!$request instanceof Request) {
            throw new RuntimeException(sprintf('Expected request at first argument but got %s', gettype($request)));
        }

        $key = '';
        for ($i = 0; $i < 16; $i++) {
            $key .= chr(mt_rand(0,255));
        }

        $key = base64_encode($key);

        $request->setKey($key);

        return $key;
    }

    /**
     * {@inheritdoc}
     *
     * Learn more here: https://tools.ietf.org/html/rfc6455#section-4.1
     */
    public function verify(AbstractHttpMessage $response, string $key = null)
    {
        if (!$response instanceof Response) {
            throw new RuntimeException(
                sprintf('The client handshake cannot verify something else than a Response object, %s given.', get_class($response))
            );
        }

        // If the http code is not 101 there's a problem.
        if ($response->getStatusCode() !== 101) {
            if ($response->getStatusCode() === 401) {
                throw new UnsupportedException(
                    'This WebSocket server needs an HTTP authentication but Woketo doesn\'t supports it for now.'
                );
            }

            throw new WebsocketException(
                sprintf('Attempting to get a 101 response from the server but got %s.', $response->getStatusCode())
            );
        }

        $remoteSignature = $response->getHeader('Sec-WebSocket-Accept');
        if (
            \strtolower($response->getHeader('Upgrade')) !== 'websocket'
            || \strtolower($response->getHeader('Connection')) !== 'upgrade'
            || empty($remoteSignature)
        ) {
            throw new WebsocketException('The server doesn\'t answer properly to the handshake.');
        }

        $signature = \base64_encode(\sha1($key . HandshakeInterface::GUID, true));

        if ($remoteSignature !== $signature) {
            throw new WebsocketException('The remote signature is invalid.');
        }

        if (!empty($response->getHeader('Sec-WebSocket-Protocol', null))) {
            throw new WebsocketException('The server specified a WebSocket subprotocol but none is supported by Woketo.');
        }

        return true;
    }

    /**
     * @param Url $url
     * @return Request
     */
    public function getRequest(Url $url)
    {
        $request = Request::createClientRequest($url->getUri(), $url->getHost(), $url->getPort());
        $request->setVersion(ClientHandshake::WEBSOCKET_VERSION);
        $request->setKey(base64_encode(ClientHandshake::generateRandom16BytesKey()));

        return $request;
    }

    /**
     * @return string
     */
    public static function generateRandom16BytesKey()
    {
        $bytes = '';

        for($i = 0; $i < 16; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }

        return $bytes;
    }
}
