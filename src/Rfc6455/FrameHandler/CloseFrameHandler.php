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
use Nekland\Woketo\Utils\BitManipulation;
use React\Socket\ConnectionInterface;

class CloseFrameHandler implements Rfc6455FrameHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(Message $message)
    {
        return $message->getFirstFrame()->getOpcode() === Frame::OP_CLOSE;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, MessageProcessor $messageProcessor, ConnectionInterface $socket)
    {
        $code = Frame::CLOSE_NORMAL;

        $frame = $message->getFirstFrame();
        $payload = $frame->getPayload();
        if ($frame->getRsv1() || $frame->getRsv2() || $frame->getRsv3()) {
            $code = Frame::CLOSE_PROTOCOL_ERROR;
        }

        if (BitManipulation::frameSize($payload) > 1) {
            $errorCode = BitManipulation::bytesFromTo($payload, 0, 1);

            // https://tools.ietf.org/html/rfc6455#section-7.4
            if (
                (
                    !\in_array($errorCode, [
                        Frame::CLOSE_NORMAL, Frame::CLOSE_GOING_AWAY, Frame::CLOSE_PROTOCOL_ERROR, Frame::CLOSE_GOING_AWAY,
                        Frame::CLOSE_WRONG_DATA, Frame::CLOSE_INCOHERENT_DATA, Frame::CLOSE_TOO_BIG_TO_PROCESS,
                        Frame::CLOSE_POLICY_VIOLATION, Frame::CLOSE_MISSING_EXTENSION, Frame::CLOSE_UNEXPECTING_CONDITION
                    ])
                    && $errorCode < 3000 && $errorCode > 900
                )
                || $errorCode > 4999
                || $errorCode < 1000
            ) {
                $code = Frame::CLOSE_PROTOCOL_ERROR;
            }
        }

        $messageProcessor->write($messageProcessor->getFrameFactory()->createCloseFrame($code), $socket);
        $socket->end();
    }
}
