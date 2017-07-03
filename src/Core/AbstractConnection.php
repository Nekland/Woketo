<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Core;

use Nekland\Woketo\Exception\NoHandlerException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Handshake\HandshakeInterface;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\SimpleLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Socket\Connection;

abstract class AbstractConnection
{
    /**
     * 5 seconds
     */
    const DEFAULT_TIMEOUT = 5;

    use LoggerAwareTrait;

    /**
     * @var Connection
     */
    protected $stream;

    /**
     * @var MessageProcessor
     */
    protected $messageProcessor;

    /**
     * @var \Nekland\Woketo\Rfc6455\Handshake\ServerHandshake|\Nekland\Woketo\Rfc6455\Handshake\ClientHandshake
     */
    protected $handshake;

    /**
     * @var bool
     */
    protected $handshakeDone;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var MessageHandlerInterface|\Closure
     */
    protected $handler;

    /**
     * @var TimerInterface
     */
    protected $timeout;

    /**
     * @var Message
     */
    protected $currentMessage;

    /**
     * @var LoopInterface
     */
    protected $loop;

    public function __construct(MessageProcessor $messageProcessor, HandshakeInterface $handshake = null)
    {
        $this->handshake = $handshake;
        $this->messageProcessor = $messageProcessor;
    }

    protected function onMessage(string $data)
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
            $this->getLogger()->notice('Connection to ' . $this->getIp() . ' closed with error : ' . $e->getMessage());
            $this->handler->onError($e, $this);
        }
    }

    protected abstract function processHandshake(string $data);
    protected abstract function processMessage(string $data);

    /**
     * @return string
     */
    public abstract function getIp();

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger ?: $this->logger = new SimpleLogger();
    }

    /**
     * @return MessageHandlerInterface
     * @throws NoHandlerException
     */
    protected function getHandler() : MessageHandlerInterface
    {
        if ($this->handler instanceof \Closure) {
            $handler = $this->handler;
            $handler = $handler($this->uri, $this);

            if (null === $handler) {
                throw new NoHandlerException(sprintf('No handler for request URI %s.', $this->uri));
            }

            $this->handler = $handler;
        }

        return $this->handler;
    }

    /**
     * Close the connection with normal close.
     */
    public function close()
    {
        $this->messageProcessor->close($this->stream);
    }

    /**
     * @param string|Frame  $frame
     * @param int           $opCode
     * @throws \Nekland\Woketo\Exception\RuntimeException
     */
    public abstract function write($frame, int $opCode = Frame::OP_TEXT);
}
