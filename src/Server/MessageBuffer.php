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


use Nekland\Woketo\Message\MessageHandlerInterface;

class MessageBuffer
{
    private $messageHandler;
    private $frames;

    public function __construct(MessageHandlerInterface $messageHandler)
    {
        $this->messageHandler;
        $this->frames = [];
    }

    public function data($data)
    {
        $frame = new Frame($data);
    }
}
