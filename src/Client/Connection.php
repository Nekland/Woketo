<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Client;


use Nekland\Woketo\Core\AbstractConnection;
use Nekland\Woketo\Exception\Http\IncompleteHttpMessageException;
use Nekland\Woketo\Exception\RuntimeException;
use Nekland\Woketo\Exception\WebsocketException;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Http\Url;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Handshake\ClientHandshake;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;

class Connection extends AbstractConnection
{
    /**
     * @var string|null
     */
    private $handshakeKey;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var Url
     */
    private $url;

    public function __construct(Url $url, PromiseInterface $clientPromise, MessageProcessor $messageProcessor, MessageHandlerInterface $handler, LoopInterface $loop)
    {
        parent::__construct($messageProcessor, $loop, new ClientHandshake());

        $this->url = $url;
        $this->uri = $this->url->getUri();
        $this->buffer = '';
        $this->handler = $handler;

        $clientPromise->then(function (ConnectionInterface $stream) {
            $this->stream = $stream;
            $this->onConnection($stream);
        }, function (\Exception $error) {
            $this->onError($error);
        });
    }

    private function onConnection(ConnectionInterface $stream)
    {
        $stream->on('data', function (string $data) {
            $this->onMessage($data);
        });

        // This is done because the handshake should come from the client.
        $this->processHandshake('');
    }

    /**
     * {@inheritdoc}
     */
    protected function processHandshake(string $data)
    {
        // Sending initialization request
        if (null === $this->handshakeKey) {
            $request = $this->handshake->getRequest($this->url);
            $this->stream->write($request->getRequestAsString());
            $this->handshakeKey = $request->getKey();

            return;
        }

        $this->buffer .= $data;

        // Receiving the response
        try {
            $response = Response::create($this->buffer);
        } catch (IncompleteHttpMessageException $e) {
            return;
        }

        // Verifying response data
        $this->handshake->verify($response, $this->handshakeKey);

        // Signaling the handshake is done to jump in the message exchange process
        $this->handshakeDone = true;
        $this->getHandler()->onConnection($this);

        if (!empty($this->buffer)) {
            $buffer = $this->buffer;
            $this->buffer = '';
            $this->onMessage($buffer);
        }
    }

    /**
     * {@inheritdoc}
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
                    $this->getLogger()->notice('Connection to ' . $this->getIp() . ' timed out.');
                    $this->messageProcessor->timeout($this->stream);
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
            $this->messageProcessor->write($frame, $this->stream, $opCode);
        } catch (WebsocketException $e) {
            throw new RuntimeException($e);
        }
    }

    /**
     * @param \Exception|string $error
     */
    private function onError($error)
    {
        $error = $error instanceof \Exception ? $error->getMessage() : $error;

        $this->getLogger()->error(sprintf('An error occured: %s', $error));
    }

    /**
     * {@inheritdoc}
     */
    public function getIp()
    {
        return $this->url->getHost();
    }
}
