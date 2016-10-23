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


use Nekland\Woketo\Exception\Frame\IncompleteFrameException;
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

    /**
     * @var string
     */
    private $buffer;

    public function __construct()
    {
        $this->frames = [];
        $this->isComplete = false;
        $this->buffer = '';
    }

    public function addData($data)
    {
        try {
            if ('' === $this->buffer) {
                $this->addFrame(new Frame($data));
            } else {
                $this->addFrame(new Frame($this->buffer . $data));
            }
            $this->buffer = '';

        } catch (IncompleteFrameException $e) {
            $this->buffer .= $data;
        }
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

        if (count($this->frames) > 19) {
            throw new LimitationException('We don\'t accept more than 20 frame by message. This is a security limitation.');
        }

        $this->isComplete = $frame->isFinal();
        $this->frames[] = $frame;

        return $this;
    }

    /**
     * @return Frame
     * @throws MissingDataException
     */
    public function getFirstFrame() : Frame
    {
        if (empty($this->frames[0])) {
            throw new MissingDataException('There is no first frame for now.');
        }

        return $this->frames[0];
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

    /**
     * @return int
     */
    public function getOpcode()
    {
        return $this->getFirstFrame()->getOpcode();
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        return $this->isComplete;
    }

    /**
     * @return bool
     */
    public function isOperation()
    {
        return in_array($this->getFirstFrame()->getOpcode(), [Frame::OP_TEXT, Frame::OP_BINARY]);
    }
}
