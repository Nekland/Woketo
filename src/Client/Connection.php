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
use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Handshake\ClientHandShake;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use React\Promise\PromiseInterface;
use React\Stream\Stream;

class Connection extends AbstractConnection
{
    /**
     * @var bool
     */
    private $requestSent;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $buffer;

    public function __construct(string $uri, string $host, PromiseInterface $clientPromise, MessageProcessor $messageProcessor)
    {
        parent::__construct($messageProcessor, new ClientHandShake());

        $this->requestSent = false;
        $this->uri = $uri;
        $this->host = $host;
        $this->buffer = '';

        $clientPromise->then(function (Stream $stream) {
            $this->stream = $stream;
            $this->onConnection($stream);
        }, function (\Exception $error){
            $this->onError($error);
        });

        // This is done because the handshake should come from the client.
        $this->processHandshake('');
    }

    private function onConnection(Stream $stream)
    {
        $stream->on('message', function (string $data) {
            $this->onMessage($data);
        });
    }

    /**
     * @param string $data
     */
    protected function processHandshake(string $data)
    {
        // Sending initialization request
        if (!$this->requestSent) {
            $request = Request::createClientRequest($this->uri, $this->host);
            $this->stream->write($request->getRequestAsString());
            return;
        }

        $this->buffer .= $data;

        // Receiving the response
        try {
            $response = Response::create($data);
        } catch (IncompleteHttpMessageException $e) {
            return;
        }

        // Verifying response data
        $this->handshake->verify($response);

        // Signaling the handshake is done to jump in the message exchange process
        $this->handshakeDone = true;
        $this->getHandler()->onConnection($this);
    }

    protected function processMessage(string $data)
    {
        // It may be a timeout going (we were waiting for data), let's clear it.
        if ($this->timeout !== null) {
            $this->timeout->cancel();
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

    /**
     * @param \Exception|string $error
     */
    private function onError($error)
    {
        $error = $error instanceof \Exception ? $error->getMessage() : $error;

        $this->logger->error(sprintf('An error occured: %s', $error));
    }
}
