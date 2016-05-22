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


use Nekland\Woketo\Exception\LimitationException;
use Nekland\Woketo\Exception\MissingDataException;

class Message
{
    /**
     * @var array
     */
    private $frames;

    /**
     * @var bool
     */
    private $isComplete;

    public function __construct()
    {
        $this->frames = [];
        $this->isComplete = false;
    }

    /**
     * @param Frame $frame
     * @return Message
     * @throws \InvalidArgumentException
     * @throws LimitationException
     */
    public function addFrame(Frame $frame) : Message
    {
        if ($this->isComplete) {
            throw new \InvalidArgumentException('The message is already complete.');
        }

        if (count($this->frames) > 9) {
            throw new LimitationException('We don\'t accept more than 10 frame by message. This is a security limitation.');
        }

        $this->isComplete = $frame->isFinal();
        $this->frames[] = $frame;

        return $this;
    }

    /**
     * This could in the future be deprecated in favor of a stream object.
     *
     * @return string
     * @throws MissingDataException
     */
    public function getContent() : string
    {
        if (!$this->isComplete) {
            throw new MissingDataException('The message is not complete. Frames are missing.');
        }

        $res = '';

        foreach ($this->frames as $frame) {
            $res .= $frame->getPayload();
        }

        return $res;
    }
    
    public function isComplete()
    {
        return $this->isComplete;
    }
}
