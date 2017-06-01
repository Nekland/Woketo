<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
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
     * Configuration for frames creation
     *
     * @var array
     */
    private $configuration;

    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
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
        $content = BitManipulation::intToBinaryString($status);
        if (null !== $reason) {
            $content .= $reason;
        }

        $frame->setPayload($content);

        return $frame;
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

        return $frame;
    }

    /**
     * Construct a frame with a global configuration.
     *
     * @param string|null $rawData
     * @return Frame
     */
    public function createNewFrame(string $rawData = null)
    {
        return new Frame($rawData, $this->configuration);
    }

    /**
     * This generates a string of 4 random bytes. (WebSocket mask according to the RFC)
     *
     * @return string
     */
    public static function generateMask() : string
    {
        return random_bytes(4);
    }
}
