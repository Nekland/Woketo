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

use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use React\Stream\Stream;

class PingFrameHandler implements Rfc6455FrameHandlerInterface
{
    public function supports(Message $message)
    {
        return $message->getFirstFrame()->getOpcode() === Frame::OP_PING;
    }

    public function process(Message $message, MessageProcessor $messageProcessor, Stream $socket)
    {
        $messageProcessor->write($messageProcessor->getFrameFactory()->createPongFrame($message->getContent()), $socket);
    }
}
