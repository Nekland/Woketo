<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455;


use Nekland\Woketo\Message\MessageHandlerInterface;
use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\FrameFactory;
use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageHandler\PingFrameHandler;
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
        $messages = iterator_to_array($processor->onData(
            // Hello normal frame
            BitManipulation::hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']),
            $this->socket->reveal()
        ));

        $this->assertInstanceOf(Message::class, $messages[0]);
        $this->assertSame('Hello', $messages[0]->getContent());
    }

    public function testItBuildManyMessagesWithOnlyOneFrameData()
    {
        $multipleFrameData = BitManipulation::hexArrayToString(
            '01', '03', '48', '65', '6c', // Data part 1
            '80', '02', '6c', '6f',       // Data part 2
            '81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58' // Another message (Hello frame)
        );

        $processor = new MessageProcessor();

        $messages = iterator_to_array($processor->onData($multipleFrameData, $this->socket->reveal()));

        $this->assertSame(count($messages), 2);
        $this->assertSame($messages[1]->getContent(), 'Hello');
    }

    public function testItContinueFrameEvaluationAfterControlFrame()
    {
        $multipleFrameData = BitManipulation::hexArrayToString(
            '89', '00',                                                       // Ping
            '81', '85', '37', 'fa', '21', '3d', '7f', '9f', '4d', '51', '58', // Another message (Hello frame)
            '89', '00'                                                        // Ping
        );

        $processor = new MessageProcessor();
        $processor->addHandler(new PingFrameHandler());

        $messages = iterator_to_array($processor->onData($multipleFrameData, $this->socket->reveal()));

        $this->assertSame(count($messages), 3);
        $this->assertSame($messages[1]->getContent(), 'Hello');
    }

    public function testItBuildPartialMessage()
    {
        $processor = new MessageProcessor();
        $socket = $this->socket->reveal();

        $messages = iterator_to_array($processor->onData(
            // "Hel" normal frame unmasked
            BitManipulation::hexArrayToString(['01', '03', '48', '65', '6c']),
            $socket
        ));

        $this->assertSame($messages[0]->isComplete(), false);

        iterator_to_array($processor->onData(
            // "lo" normal frame unmasked
            BitManipulation::hexArrayToString(['80', '02', '6c', '6f']),
            $socket,
            $messages[0]
        ));

        $this->assertSame($messages[0]->isComplete(), true);
        $this->assertSame($messages[0]->getContent(), 'Hello');
    }

    public function testItHandleSpecialMessagesWithHandler()
    {
        $processor = new MessageProcessor();
        $this->assertSame($processor->addHandler(new class() implements Rfc6455MessageHandlerInterface {
            public function supports(Message $message)
            {
                return $message->getFirstFrame()->getOpcode() === Frame::OP_CLOSE;
            }

            public function process(Message $message, MessageProcessor $messageProcessor, ConnectionInterface $socket)
            {
                $messageProcessor->write((new FrameFactory())->createCloseFrame(), $socket);
            }
        }), $processor);

        $this->socket->write(Argument::cetera())->shouldBeCalled();

        $messages = iterator_to_array($processor->onData(
            BitManipulation::hexArrayToString(['88', '02', '03', 'E8']),
            $this->socket->reveal()
        ));

        $this->assertSame($messages[0]->getOpcode(), Frame::OP_CLOSE);
    }

    public function testItReturnTheFrameFactory()
    {
        $processor = new MessageProcessor();

        $this->assertInstanceOf(FrameFactory::class, $processor->getFrameFactory());
    }

    public function testItWritesFrames()
    {
        $this->socket->write(BitManipulation::hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f']))->shouldBeCalled();
        $this->socket->write('foo')->shouldBeCalled();
        $frame = $this->prophesize(Frame::class);
        $frame->getRawData()->willReturn('foo');

        $processor = new MessageProcessor();
        $processor->write('Hello', $this->socket->reveal());
        $processor->write($frame->reveal(), $this->socket->reveal());
    }
}


