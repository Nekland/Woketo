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
}
