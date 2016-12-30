<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Exception;

class SocketException extends \Exception
{
    /**
     * SocketException constructor.
     * @param string   $message Error message
     * @param resource $socket  Socket experimenting the error.
     */
    public function __construct($message, $socket)
    {
        parent::__construct($message . ' (error: ' . \socket_strerror(\socket_last_error($socket)) . ')');
    }
}
