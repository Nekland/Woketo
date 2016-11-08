<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Rfc6455;

use Nekland\Woketo\Exception\Frame\TooBigControlFrameException;
use Nekland\Woketo\Exception\LimitationException;
use Nekland\Woketo\Rfc6455\MessageHandler\Rfc6455MessageHandlerInterface;
use React\Socket\ConnectionInterface;

/**
 * Class MessageProcessor
 *
 * This class is only a helper for Connection to avoid having so much instances of classes in memory.
 * Using it like that allow us to have only one instance of MessageProcessor.
 */
class MessageProcessor
{
    /**
     * @var FrameFactory
     */
    private $frameFactory;

    /**
     * @var Rfc6455MessageHandlerInterface[]
     */
    private $handlers;

    public function __construct(FrameFactory $factory = null)
    {
        $this->frameFactory = $factory ?: new FrameFactory();
        $this->handlers = [];
    }

    /**
     * @param string $data
     * @param ConnectionInterface $socket
     * @param Message|null $message
     * @return \Generator
     */
    public function onData(string $data, ConnectionInterface $socket, Message $message = null)
    {
        do {

            if (null === $message) {
                $message = new Message();
            }

            try {
                $data = $message->addData($data);

                if ($message->isComplete()) {
                    foreach ($this->handlers as $handler) {
                        if ($handler->supports($message)) {
                            $handler->process($message, $this, $socket);
                        }
                    }

                    yield $message;
                    $message = null;
                } else {
                    yield $message;
                }
            } catch (TooBigControlFrameException $e) {
                $this->write($this->frameFactory->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR), $socket);
                $socket->end();
                $data = '';
            } catch (LimitationException $e) {
                $this->write($this->frameFactory->createCloseFrame(Frame::CLOSE_TOO_BIG_TO_PROCESS), $socket);
                $socket->end();
                $data = '';
            }
        } while(!empty($data));
    }

    /**
     * @param Rfc6455MessageHandlerInterface $handler
     * @return self
     */
    public function addHandler(Rfc6455MessageHandlerInterface $handler)
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * @param Frame|string        $frame
     * @param ConnectionInterface $socket
     * @param int                 $opCode An int representing binary or text data (const of Frame class)
     */
    public function write($frame, ConnectionInterface $socket, int $opCode = Frame::OP_TEXT)
    {
        if (!$frame instanceof Frame) {
            $data = $frame;
            $frame = new Frame();
            $frame->setPayload($data);
            $frame->setOpcode($opCode);
        }

        $socket->write($frame->getRawData());
    }

    /**
     * @return FrameFactory
     */
    public function getFrameFactory(): FrameFactory
    {
        return $this->frameFactory;
    }

    /**
     * @param ConnectionInterface $socket
     */
    public function timeout(ConnectionInterface $socket)
    {
        $this->write($this->frameFactory->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR), $socket);
        $socket->close();
    }

    /**
     * @param ConnectionInterface $socket
     */
    public function close(ConnectionInterface $socket)
    {
        $this->write($this->frameFactory->createCloseFrame(), $socket);
    }
}
