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


class Frame
{
    private $rawData;
    private $frameSize;

    public function __construct($data)
    {
        $this->rawData = $data;
        $this->frameSize = strlen($data);
        if ($this->frameSize < 2) {
            throw new \InvalidArgumentException('Not enough data to be a frame.');
        }
    }

    /**
     * As a message is composed by many frames, the frame have the information of "last" or not.
     * The frame is final if the first bit is 0.
     */
    public function isFinal()
    {
        return 128 === (ord($this->rawData[0]) & 128);
    }
}
