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

use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Http\AbstractHttpMessage;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;

interface HandshakeInterface
{
    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const SUPPORTED_VERSIONS = [13];

    /**
     * Used when doing the handshake to encode the key, verifying client/server are speaking the same language
     * @param AbstractHttpMessage $message
     * @param string              $key
     * @return string
     */
    public function sign(AbstractHttpMessage $message, string $key = null);

    /**
     * @param AbstractHttpMessage $message
     * @param string              $key
     * @return bool
     * @throws WebsocketException
     */
    public function verify(AbstractHttpMessage $message, string $key = null);
}
