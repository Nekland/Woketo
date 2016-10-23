<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Rfc6455;

use Nekland\Woketo\Utils\BitManipulation;

/**
 * Class FrameFactory
 *
 * This class generates Frame objects for control frames.
 * https://tools.ietf.org/html/rfc6455#section-5.5
 *
 * Notice: a control frame cannot be larger than 125 bytes.
 */
class FrameFactory
{
    /**
     * @param int    $status One of the close constant code of Frame class.
     * @param string $reason A little message that explain why closing.
     * @return Frame
     */
    public function createCloseFrame(int $status = Frame::CLOSE_NORMAL, string $reason = null) : Frame
    {
        $frame = $this->createNewFrame();

        $frame->setOpcode(Frame::OP_CLOSE);
        $content = BitManipulation::intToString($status);
        if (null !== $reason) {
            $content .= $reason;
        }

        $frame->setPayload($content);

        return $frame;
    }

    /**
     * The payload of Pong must be empty
     * @return Frame
     */
    public function createPongFrame() : Frame
    {
        $frame = $this->createNewFrame();

        $frame->setOpcode(Frame::OP_PONG);
        $frame->setPayload('');

        return $frame;
    }

    protected function createNewFrame()
    {
        return new Frame();
    }
}
