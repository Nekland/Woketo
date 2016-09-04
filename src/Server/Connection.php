<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Server;

use Nekland\Woketo\Exception\Frame\IncompleteFrameException;
use Nekland\Woketo\Exception\Frame\TooBigFrameException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\ServerHandshake;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Socket\ConnectionInterface;

class Connection
{
    /**
     * 5 seconds
     */
    const DEFAULT_TIMEOUT = 5;

    /**
     * @var ConnectionInterface
     */
    private $socketStream;

    /**
     * @var MessageHandlerInterface
     */
    private $handler;

    /**
     * @var bool
     */
    private $handshakeDone;

    /**
     * @var ServerHandshake
     */
    private $handshake;

    /**
     * @var Message
     */
    private $currentMessage;

    /**
     * @var FrameFactory
     */
    private $frameFactory;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var TimerInterface
     */
    private $timeout;
    
    public function __construct(ConnectionInterface $socketStream, MessageHandlerInterface $messageHandler, LoopInterface $loop, ServerHandshake $handshake = null, FrameFactory $frameFactory = null)
    {
        $this->socketStream = $socketStream;
        $this->socketStream->on('data', [$this, 'processData']);
        $this->socketStream->on('error', [$this, 'error']);
        $this->handler = $messageHandler;
        $this->handshake = $handshake ?: new ServerHandshake;
        $this->frameFactory = $frameFactory ?: new FrameFactory();
        $this->loop = $loop;
        $this->buffer = null;
    }

    /**
     * @param string $data
     */
    protected function processMessage($data)
    {
        if ($this->currentMessage === null || $this->currentMessage->isComplete()) {
            $this->currentMessage = new Message();
        }
        if (null === $this->buffer) {
            $this->buffer = $data;
        } else {
            $this->buffer .= $data;
        }

        try {
            if ($this->timeout !== null) {
                $this->timeout->cancel();
                $this->timeout = null;
            }

            $this->currentMessage->addFrame(new Frame($this->buffer));
            $this->buffer = null;
            if ($this->currentMessage->isComplete()) {
                $this->handler->onData($this->currentMessage->getContent(), $this);
            }
        } catch (IncompleteFrameException $e) {

            $this->timeout = $this->loop->addTimer(Connection::DEFAULT_TIMEOUT, function () {
                $this->write($this->frameFactory->createCloseFrame(Frame::CLOSE_PROTOCOL_ERROR));
                $this->socketStream->close();
            });
        }
    }

    public function processData($data)
    {
        try {
            if (!$this->handshakeDone) {
                $this->processHandcheck($data);
            } else {
                $this->processMessage($data);
            }

            return;
        } catch (TooBigFrameException $e) {
            $this->write($this->frameFactory->createCloseFrame(Frame::CLOSE_TOO_BIG_TO_PROCESS));
            $this->handler->onError($e, $this);
        } catch (WebsocketException $e) {
            $this->write($this->frameFactory->createCloseFrame());
            $this->handler->onError($e, $this);
        }

        $this->socketStream->close();
    }

    /**
     * @param string $frame
     * @throws \Nekland\Woketo\Exception\Frame\InvalidFrameException
     */
    public function write($frame)
    {
        if (!$frame instanceof Frame) {
            $data = $frame;
            $frame = new Frame();
            $frame->setPayload($data);
            $frame->setOpcode(Frame::OP_TEXT);
        }
        $this->socketStream->write($frame->getRawData());
    }

    /**
     * @param $data
     */
    public function error($data)
    {
        echo "There is an error : \n" . $data . "\n\n";
    }

    /**
     * If it's a new client, we need to make some special actions named the handshake.
     *
     * @param string $data
     */
    protected function processHandcheck($data)
    {
        if ($this->handshakeDone) {
            return;
        }

        $request = Request::create($data);
        $this->handshake->verify($request);
        $response = Response::createSwitchProtocolResponse();
        $this->handshake->sign($request, $response);
        $response->send($this->socketStream);
        
        $this->handshakeDone = true;
        $this->handler->onConnection($this);
    }
}
