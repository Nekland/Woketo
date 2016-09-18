<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455;


use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageHandler\Rfc6455MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;
use Prophecy\Argument;
use React\Socket\ConnectionInterface;

class MessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    private $socket;

    public function setUp()
    {
        parent::setUp();

        $this->socket = $this->prophesize(ConnectionInterface::class);
    }

    public function testItBuildMessagesUsingMessageClass()
    {
        $processor = new MessageProcessor();

        /** @var Message $message */
        $message = $processor->onData(
            // Hello normal frame
            BitManipulation::hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']),
            $this->socket->reveal()
        );

        $this->assertInstanceOf($message, Message::class);
        $this->assertSame('Hello', $message->getContent());
    }

    public function testItBuildPartialMessage()
    {
        $processor = new MessageProcessor();
        $socket = $this->socket->reveal();

        $message = $processor->onData(
            // "Hel" normal frame unmasked
            BitManipulation::hexArrayToString(['01', '03', '48', '65', '6c']),
            $socket
        );

        $this->assertSame($message->isComplete(), false);

        $processor->onData(
            // "lo" normal frame unmasked
            BitManipulation::hexArrayToString(['80', '02', '6c', '6f']),
            $socket,
            $message
        );

        $this->assertSame($message->isComplete(), true);
        $this->assertSame($message->getContent(), 'Hello');
    }

    public function testItHandleSpecialMessagesWithHandler()
    {
        $processor = new MessageProcessor();
        $processor->addHandler(new class() implements Rfc6455MessageHandlerInterface {
            public function supports(Frame $frame)
            {
                return $frame->getOpcode() === Frame::OP_CLOSE;
            }

            public function process(Frame $frame, MessageProcessor $messageProcessor)
            {
                $messageProcessor->write((new FrameFactory())->createCloseFrame());
            }
        });

        $this->socket->write(Argument::cetera())->shouldBeCalled();

        $message = $processor->onData(
            BitManipulation::hexArrayToString(['88', '02', '03', 'E8']),
            $this->socket->reveal()
        );

        $this->assertSame($message, null);
    }


}


