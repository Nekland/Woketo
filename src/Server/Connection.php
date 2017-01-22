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

use Nekland\Woketo\Exception\NoHandlerException;
use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Rfc6455\ServerHandshake;
use Psr\Log\LoggerAwareTrait;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Socket\ConnectionInterface;

class Connection
{
    use LoggerAwareTrait;

    /**
     * 5 seconds
     */
    const DEFAULT_TIMEOUT = 5;

    /**
     * @var ConnectionInterface
     */
    private $socketStream;

    /**
     * @var MessageHandlerInterface|\Closure
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

    /**
     * @var string
     */
    private $uri;
    
    public function __construct(
        ConnectionInterface $socketStream,
        \Closure $messageHandler,
        LoopInterface $loop,
        MessageProcessor $messageProcessor,
        ServerHandshake $handshake = null
    ) {
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
        $this->socketStream->on('error', function ($data) {
            $this->error($data);
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
            $this->logger->notice('Connection to ' . $this->getIp() . ' closed with error : ' . $e->getMessage());
            $this->getHandler()->onError($e, $this);
        } catch (NoHandlerException $e) {
            $this->getLogger()->info(sprintf('No handler found for uri %s. Connection closed.', $this->uri));
            $this->close();
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
    protected function processHandcheck($data)
    {
        if ($this->handshakeDone) {
            return;
        }

        $request = Request::create($data);
        $this->handshake->verify($request);
        $this->uri = $request->getUri();
        $response = Response::createSwitchProtocolResponse();
        $this->handshake->sign($request, $response);
        $response->send($this->socketStream);
        
        $this->handshakeDone = true;
        $this->getHandler()->onConnection($this);
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->socketStream->getRemoteAddress();
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Close the connection with normal close.
     */
    public function close()
    {
        $this->messageProcessor->close($this->socketStream);
    }

    /**
     * @return MessageHandlerInterface
     * @throws NoHandlerException
     */
    private function getHandler()
    {
        if ($this->handler instanceof \Closure) {
            $handler = $this->handler;
            $handler = $handler($this->uri, $this);

            if (null === $handler) {
                throw new NoHandlerException(sprintf('No handler for request URI %s.', $this->uri));
            }

            return $this->handler = $handler;
        }

        return $this->handler;
    }
}
