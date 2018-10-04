<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Server;

use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Exception\NoHandlerException;
use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Rfc6455\Handshake\ServerHandshake;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

class Connection extends AbstractConnection
{
    public function __construct(
        ConnectionInterface $socketStream,
        \Closure $messageHandler,
        LoopInterface $loop,
        MessageProcessor $messageProcessor,
        ServerHandshake $handshake = null
    ) {
        parent::__construct($messageProcessor, $loop, $handshake ?: new ServerHandshake);
        $this->stream = $socketStream;
        $this->initListeners();
        $this->handler = $messageHandler;
    }

    private function initListeners()
    {
        $this->stream->on('data', function ($data) {
            $this->processData($data);
        });
        $this->stream->once('end', function() {
            $this->getHandler()->onDisconnect($this);
        });
        $this->stream->on('error', function ($data) {
            $this->error($data);
        });
    }

    private function processData($data)
    {
        try {
            if (!$this->handshakeDone) {
                $this->processHandshake($data);
            } else {
                $this->processMessage($data);
            }

            return;
        } catch (WebsocketException $e) {
            $this->messageProcessor->close($this->stream);
            $this->logger->notice('Connection to ' . $this->getIp() . ' closed with error : ' . $e->getMessage());
            $this->getHandler()->onError($e, $this);
        } catch (NoHandlerException $e) {
            $this->getLogger()->info(sprintf('No handler found for uri %s. Connection closed.', $this->uri));
            $this->close(Frame::CLOSE_WRONG_DATA);
        }
    }

    /**
     * This method build a message and buffer data in case of incomplete data.
     *
     * @param string $data
     */
    protected function processMessage(string $data)
    {
        // It may be a timeout going (we were waiting for data), let's clear it.
        if ($this->timeout !== null) {
            $this->loop->cancelTimer($this->timeout);
            $this->timeout = null;
        }

        foreach ($this->messageProcessor->onData($data, $this->stream, $this->currentMessage) as $message) {
            $this->currentMessage = $message;
            if ($this->currentMessage->isComplete()) {
                // Sending the message through the woketo API.
                switch($this->currentMessage->getOpcode()) {
                    case Frame::OP_TEXT:
                        $this->getHandler()->onMessage($this->currentMessage->getContent(), $this);
                        break;
                    case Frame::OP_BINARY:
                        $this->getHandler()->onBinary($this->currentMessage->getContent(), $this);
                        break;
                }
                $this->currentMessage = null;

            } else {
                // We wait for more data so we start a timeout.
                $this->timeout = $this->loop->addTimer(Connection::DEFAULT_TIMEOUT, function () {
                    $this->logger->notice('Connection to ' . $this->getIp() . ' timed out.');
                    $this->messageProcessor->timeout($this->stream);
                });
            }
        }
    }

    public function write($frame, int $opCode = Frame::OP_TEXT)
    {
        try {
            $this->messageProcessor->write($frame, $this->stream, $opCode);
        } catch (WebsocketException $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * @param mixed $data
     */
    protected function error($data)
    {
        $message = "A connectivity error occurred: " . $data;
        $this->logger->error($message);
        $this->getHandler()->onError(new WebsocketException($message), $this);
    }

    /**
     * If it's a new client, we need to make some special actions named the handshake.
     *
     * @param string $data
     */
    protected function processHandshake(string $data)
    {
        if ($this->handshakeDone) {
            return;
        }

        $request = Request::create($data);
        $this->handshake->verify($request);
        $this->uri = $request->getUri();
        $response = Response::createSwitchProtocolResponse();
        $this->handshake->sign($response, $this->handshake->extractKeyFromRequest($request));
        $response->send($this->stream);

        $this->handshakeDone = true;
        $this->getHandler()->onConnection($this);
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->stream->getRemoteAddress();
    }
}
