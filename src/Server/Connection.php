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

use Nekland\Woketo\Http\Request;
use Nekland\Woketo\Http\Response;
use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\ServerHandshake;
use React\Socket\ConnectionInterface;

class Connection
{
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
    
    public function __construct(ConnectionInterface $socketStream, MessageHandlerInterface $messageHandler, ServerHandshake $handshake = null)
    {
        $this->socketStream = $socketStream;
        $this->socketStream->on('data', [$this, 'handcheck']);
        $this->socketStream->on('data', [$this, 'message']);
        $this->socketStream->on('error', [$this, 'error']);
        $this->handler = $messageHandler;
        $this->handshake = $handshake ?: new ServerHandshake;
    }

    /**
     * @param string $data
     */
    public function message($data)
    {
        if (!$this->handshakeDone) {
            return;
        }
        
        if ($this->currentMessage === null || $this->currentMessage->isComplete()) {
            $this->currentMessage = new Message();
        }
        
        $this->currentMessage->addFrame(new Frame($data));
        if ($this->currentMessage->isComplete()) {
            $this->handler->onData($this->currentMessage->getContent(), $this);
        }
    }

    /**
     * @param string $data
     * @throws \Nekland\Woketo\Exception\InvalidFrameException
     */
    public function write($data)
    {
        $frame = new Frame();
        $frame->setPayload($data);
        $frame->setOpcode(Frame::OP_TEXT);
        $this->write($frame->getRawData());
    }
    
    public function error($data)
    {
        echo "OMG\n" . $data . "\n\n";
    }

    public function processHandcheck($data)
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
