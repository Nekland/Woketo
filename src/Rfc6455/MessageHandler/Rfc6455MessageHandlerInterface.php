<?php

namespace Nekland\Woketo\Rfc6455\MessageHandler;

use Nekland\Woketo\Rfc6455\Frame;
use Nekland\Woketo\Rfc6455\MessageProcessor;

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
interface Rfc6455MessageHandlerInterface
{
    /**
     * @param Frame $frame
     * @return boolean
     */
    public function supports(Frame $frame);

    /**
     * @param Frame $frame
     * @param MessageProcessor $messageProcessor
     * @return null
     */
    public function process(Frame $frame, MessageProcessor $messageProcessor);
}
