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

use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
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
    
    public function __construct(ConnectionInterface $socketStream, MessageHandlerInterface $messageHandler, LoopInterface $loop, MessageProcessor $messageProcessor, ServerHandshake $handshake = null)
    {
        $this->socketStream = $socketStream;
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
        $this->socketStream->on('error', [$this, 'error']);
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
        // It may be a timeout going (we were waiting for data), let's clear it.
        if ($this->timeout !== null) {
            $this->timeout->cancel();
            $this->timeout = null;
        }

        foreach ($this->messageProcessor->onData($data, $this->socketStream, $this->currentMessage) as $message) {
            $this->currentMessage = $message;
            if ($this->currentMessage->isComplete()) {
                // Sending the message through the woketo API.
                switch($this->currentMessage->getOpcode()) {
                    case Frame::OP_TEXT:
                        $this->handler->onMessage($this->currentMessage->getContent(), $this);
                        break;
                    case Frame::OP_BINARY:
                        $this->handler->onBinary($this->currentMessage->getContent(), $this);
                        break;
                }
                $this->currentMessage = null;

            } else {
                // We wait for more data so we start a timeout.
                $this->timeout = $this->loop->addTimer(Connection::DEFAULT_TIMEOUT, function () {
                    $this->messageProcessor->timeout($this->socketStream);
                });
            }
        }

    }

    /**
     * @param string|Frame $frame
     * @param int          $opCode An int representing binary or text data (const of Frame class)
     * @throws \Nekland\Woketo\Exception\RuntimeException
     */
    public function write($frame, int $opCode = Frame::OP_TEXT)
    {
        try {
            $this->messageProcessor->write($frame, $this->socketStream, $opCode);
        } catch (WebsocketException $e) {
            throw new RuntimeException($e);
        }
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
