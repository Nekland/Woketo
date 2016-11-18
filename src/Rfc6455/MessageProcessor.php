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

use Nekland\Woketo\Exception\Frame\IncoherentDataException;
use Nekland\Woketo\Exception\Frame\IncompleteFrameException;
use Nekland\Woketo\Exception\Frame\ProtocolErrorException;
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
     * This methods process data received from the socket to generate a `Message` entity and/or process handler
     * which may answer to some special frames.
     *
     * (binary data entry string in {}, frames in || and messages (of potentially many frames) in [])
     * This method buffer in many ways:
     *
     * - { [|frame1 (not final) |, |frame2 (final)|] }
     *   => buffer 2 frames from 1 binary to generate 1 message
     *
     * - { [|frame1 (not final, not finished } { frame 1 (final, finished)| } { |frame 2 (final, finished)|] }
     *   => buffer 2 frames from 3 binary data to generate 1 message
     *
     * - { [|frame1 (not final, not finished } { frame control 1 (final, finished)| } { |frame 2 (final, finished)|] }
     *   => buffer 2 frames from 3 binary data to generate 1 ping and 1 message
     *
     * @param string              $data
     * @param ConnectionInterface $socket
     * @param Message|null        $message
     * @return \Generator
     */
    public function onData(string $data, ConnectionInterface $socket, Message $message = null)
    {
        do {
            if (null === $message) {
                $message = new Message();
            }

            try {
                $message->addBuffer($data);

                // Loop that build message if the message is in many frames in the same data binary frame received.
                do {
                    try {
                        $frame = new Frame($message->getBuffer());

                        if ($frame->isControlFrame()) {
                            $controlFrameMessage = $this->processControlFrame($frame, $socket);

                            yield $controlFrameMessage; // Because every message should be returned !
                        } else {
                            $message->addFrame($frame);
                        }

                        // If the frame is a success maybe we still need to create messages
                        // And the buffer must be updated
                        $data = $message->removeFromBuffer($frame);
                    } catch (IncompleteFrameException $e) {
                        // Data is now stored in the message, let's clean the variable to stop both loops.
                        $data = null;
                    }
                } while(!$message->isComplete() && !empty($data));

                if ($message->isComplete()) {
                    $this->processHelper($message, $socket);

                    yield $message;
                    $message = null;
                } else if ($message->countFrames() > 0) {
                    yield $message;
                }
            } catch (IncoherentDataException $e) {
                $this->write($this->frameFactory->createCloseFrame(Frame::CLOSE_INCOHERENT_DATA), $socket);
                $socket->end();
                $data = '';
            } catch (ProtocolErrorException $e) {
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
     * @param Message $message
     * @param ConnectionInterface $socket
     */
    protected function processHelper(Message $message, ConnectionInterface $socket)
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($message)) {
                $handler->process($message, $this, $socket);
            }
        }
    }

    /**
     * @param Frame $frame
     * @param ConnectionInterface $socket
     *
     * @return Message
     */
    protected function processControlFrame(Frame $frame, ConnectionInterface $socket) : Message
    {
        $controlFrameMessage = new Message();
        $controlFrameMessage->addFrame($frame);
        $this->processHelper($controlFrameMessage, $socket);

        return $controlFrameMessage;
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
