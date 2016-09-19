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
use Nekland\Woketo\Exception\LimitationException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageProcessor;
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
     * @var MessageProcessor
     */
    private $messageProcessor;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var TimerInterface
     */
    private $timeout;
    
    public function __construct(ConnectionInterface $socketStream, MessageHandlerInterface $messageHandler, LoopInterface $loop, ServerHandshake $handshake = null, MessageProcessor $messageProcessor)
    {
        $this->socketStream = $socketStream;
        $this->socketStream->on('data', [$this, 'processData']);
        $this->socketStream->on('error', [$this, 'error']);
        $this->initListeners();
        $this->handler = $messageHandler;
        $this->handshake = $handshake ?: new ServerHandshake;
        $this->loop = $loop;
        $this->messageProcessor = $messageProcessor;
    }

    private function initListeners()
    {
        $this->socketStream->on('data', function ($data) {
            $this->processData($data);
        });
    }

    private function processData($data)
    {
        try {
            if (!$this->handshakeDone) {
                $this->processHandcheck($data);
            } else {
                $this->processMessage($data);
            }

            return;
        } catch (WebsocketException $e) {
            $this->messageProcessor->close($this->socketStream);
            $this->handler->onError($e, $this);
        }
    }

    /**
     * This method build a message and buffer data in case of incomplete data.
     *
     * @param string $data
     */
    protected function processMessage($data)
    {
        try {
            // It may be a timeout going (we were waiting for data), let's clear it.
            if ($this->timeout !== null) {
                $this->timeout->cancel();
                $this->timeout = null;
            }

            $this->currentMessage = $this->messageProcessor->onData($data, $this->socketStream, $this->currentMessage);

            if (null !== $this->currentMessage && $this->currentMessage->isComplete()) {
                if (!in_array($this->currentMessage->getFirstFrame()->getOpcode(), [Frame::OP_BINARY, Frame::OP_TEXT])) {

                    // Sending the message throw the woketo API.
                    $this->handler->onData($this->currentMessage->getContent(), $this);
                    $this->currentMessage = null;
                }
            } else if (null !== $this->currentMessage && !$this->currentMessage->isComplete()) {

                // We wait for more data so we start a timeout.
                $this->timeout = $this->loop->addTimer(Connection::DEFAULT_TIMEOUT, function () {
                    $this->messageProcessor->timeout($this->socketStream);
                });
            }
        } catch (IncompleteFrameException $e) {

            // We wait for more data so we start a timeout.
            $this->timeout = $this->loop->addTimer(Connection::DEFAULT_TIMEOUT, function () {
                $this->messageProcessor->timeout($this->socketStream);
            });
        }
    }

    /**
     * @param string $frame
     * @throws \Nekland\Woketo\Exception\Frame\InvalidFrameException
     */
    public function write($frame)
    {
        $this->messageProcessor->write($frame, $this->socketStream);
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
