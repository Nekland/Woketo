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
use React\Socket\ConnectionInterface;

class WrongOpcodeFrameHandler implements Rfc6455FrameHandlerInterface
{
    /**
     * The following opcode are wrong for now but may represent something in the future depending on how the spec evolves.
     * https://tools.ietf.org/html/rfc6455#section-5.2
     *
     * @param Message $message
     * @return bool
     */
    public function supports(Message $message)
    {
        return \in_array($message->getOpcode(), [3, 4, 5, 6, 7, 11, 12, 13, 14, 15]);
    }

    /**
     * @param Message             $message
     * @param MessageProcessor    $messageProcessor
     * @param ConnectionInterface $socket
     * @return null|void
     */
    public function process(Message $message, MessageProcessor $messageProcessor, ConnectionInterface $socket)
    {
        $messageProcessor->write($messageProcessor->getFrameFactory()->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR), $socket);
        $socket->end();
    }
}
