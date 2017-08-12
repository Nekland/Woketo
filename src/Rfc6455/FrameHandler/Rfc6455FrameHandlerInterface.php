<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Rfc6455\FrameHandler;

use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use React\Socket\ConnectionInterface;

interface Rfc6455FrameHandlerInterface
{
    /**
     * @param Message $message
     * @return boolean
     */
    public function supports(Message $message);

    /**
     * @param Message             $message
     * @param MessageProcessor    $messageProcessor
     * @param ConnectionInterface $socket
     * @return null
     */
    public function process(Message $message, MessageProcessor $messageProcessor, ConnectionInterface $socket);
}
