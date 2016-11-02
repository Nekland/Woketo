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
     * @var FrameGenerator
     */
    private $generator;

    public function __construct(FrameGenerator $generator)
    {
        $this->generator = $generator;
    }

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

        return $this->returnFrame($frame);
    }

    /**
     * @param string $payload The payload must be the message content of the Ping
     * @return Frame
     */
    public function createPongFrame(string $payload) : Frame
    {
        $frame = $this->createNewFrame();

        $frame->setOpcode(Frame::OP_PONG);
        $frame->setPayload($payload);

        return $this->returnFrame($frame);
    }

    public function createFrameFromRawData(string $data)
    {
        return $this->returnFrame($this->createNewFrame()->setRawData($data));
    }

    protected function createNewFrame()
    {
        return new Frame();
    }

    /**
     * Calculate raw of the frame (if needed)
     *
     * @param Frame $frame
     * @return Frame
     */
    private function returnFrame(Frame $frame) : Frame
    {
        if ($frame->getRawData() === null) {
            $frame->setRawData($this->generator->getRawFrame($frame));
        }

        if ($frame->isBuild()) {
            $this->generator->buildFrame($frame);
        }

        return $frame;
    }
}
