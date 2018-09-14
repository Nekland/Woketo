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
use Nekland\Woketo\Rfc6455\FrameHandler\Rfc6455FrameHandlerInterface;
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
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var Rfc6455FrameHandlerInterface[]
     */
    private $handlers;

    /**
     * @var boolean
     */
    private $writeMasked;

    public function __construct($writeMasked = false, FrameFactory $factory = null, MessageFactory $messageFactory = null)
    {
        $this->writeMasked = $writeMasked;
        $this->frameFactory = $factory ?: new FrameFactory();
        $this->messageFactory = $messageFactory ?: new MessageFactory();
        $this->handlers = [];
    }

    /**
     * This methods process data received from the socket to generate a `Message` entity and/or process handler
     * which may answer to some special ws-frames.
     *
     * Legend:
     *     - {} stands for bin-frames
     *     - || stands for ws-frames
     *     - [] stands for Messages (of potentially many ws-frames)
     *     - () are here for comment purpose
     *
     * This method buffer in many ways:
     *
     * - { [|ws-frame1 (not final) |, |ws-frame2 (final)|] }
     *   => buffer 2 ws-frames from 1 bin-frame to generate 1 Message
     *
     * - { [|ws-frame1 (not final) } { ws-frame 1 (final)| } { |ws-frame 2 (final)|] }
     *   => buffer 2 ws-frames from 3 bin-frame to generate 1 Message
     *
     * - { [|ws-frame1 (not final)| |ws-frame 2 (final, control frame, is not part of the current message)| |ws-frame3 (final, with ws-frame1)|] }
     *   => buffer 2 ws-frames from 1 bin-frame to generate 1 Message with a control frame in the middle of the bin-frame.
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
                $message = $this->messageFactory->create();
            }

            try {
                $message->addBuffer($data);

                // Loop that build message if the message is in many frames in the same data binary frame received.
                do {
                    try {
                        $frame = $this->frameFactory->createNewFrame($message->getBuffer());

                        // This condition intercept control frames in the middle of normal frames
                        if ($frame->isControlFrame() && $message->hasFrames()) {
                            $controlFrameMessage = $this->processControlFrame($frame, $socket);

                            yield $controlFrameMessage; // Because every message should be returned !
                        } else {
                            if ($frame->getOpcode() === Frame::OP_CONTINUE && !$message->hasFrames()) {
                                throw new ProtocolErrorException('The first frame cannot be a continuation frame');
                            }

                            if ($frame->getOpcode() !== Frame::OP_CONTINUE && $message->hasFrames()) {
                                throw new ProtocolErrorException(
                                    'When the Message is fragmented in many frames the only frame that can be a something else than an continue frame is the first'
                                );
                            }
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
                } else {
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
     * @param Message             $message
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
     * @param Frame               $frame
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
     * @param Rfc6455FrameHandlerInterface $handler
     * @return self
     */
    public function addHandler(Rfc6455FrameHandlerInterface $handler)
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

        if ($this->writeMasked) {
            $frame->setMaskingKey(FrameFactory::generateMask());
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
     * @param int $status
     * @param string|null $reason
     */
    public function close(ConnectionInterface $socket, int $status = Frame::CLOSE_NORMAL, string $reason = null)
    {
        $this->write($this->frameFactory->createCloseFrame($status, $reason), $socket);
        $socket->end();
    }
}
