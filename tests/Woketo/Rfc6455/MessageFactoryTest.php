<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <nekland.fr@gmail.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Test\Woketo\Rfc6455;


use Nekland\Woketo\Rfc6455\Message;
use Nekland\Woketo\Rfc6455\MessageFactory;
use PHPUnit\Framework\TestCase;

class MessageFactoryTest extends TestCase
{
    public function testItCreateMessages()
    {
        $factory = new MessageFactory();
        $message = $factory->create();
        
        $this->assertInstanceOf(Message::class, $message);
    }
}
