<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Message;


use Nekland\Woketo\Exception\UnsupportedException;
use Nekland\Woketo\Server\Connection;

abstract class TextMessageHandler extends SimpleMessageHandler
{
    public function onBinary(string $data, Connection $connection)
    {
        throw new UnsupportedException('Only text is authorized by TextMessageHandler.');
    }
}
