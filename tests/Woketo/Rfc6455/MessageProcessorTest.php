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


use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\MessageProcessor;
use Nekland\Woketo\Utils\BitManipulation;

class MessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testItBuildMessagesUsingMessageClass()
    {
        $processor = new MessageProcessor($this->socket, $this->connection);
        $message = $processor->onData(
            // Hello normal frame
            BitManipulation::hexArrayToString(['81', '05', '48', '65', '6c', '6c', '6f'])
        );

        $this->assertInstanceOf($message, Frame::class);
    }
}
