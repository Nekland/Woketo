<?php

namespace Nekland\Woketo\Rfc6455\MessageHandler;

use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use React\Socket\ConnectionInterface;

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
interface Rfc6455MessageHandlerInterface
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
