<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Rfc6455\MessageHandler;

use Nekland\Woketo\Exception\Frame\TooBigFrameException;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use React\Socket\ConnectionInterface;

class PingFrameHandler implements Rfc6455MessageHandlerInterface
{
    public function supports(Message $message)
    {
        return $message->getFirstFrame()->getOpcode() === Frame::OP_PING;

    }

    public function process(Message $message, MessageProcessor $messageProcessor, ConnectionInterface $socket)
    {
        try {
            $message->checkControlFrameSize();
        } catch (TooBigFrameException $e) {
            $messageProcessor->timeout($socket);
        }

        $messageProcessor->write($messageProcessor->getFrameFactory()->createPongFrame($message->getContent()), $socket);
        $messageProcessor->write($messageProcessor->getFrameFactory()->createCloseFrame(), $socket);
        $socket->end();
    }
}
