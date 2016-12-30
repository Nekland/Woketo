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

use Nekland\Tools\StringTools;
use Nekland\Woketo\Exception\Frame\WrongEncodingException;
use Nekland\Woketo\Exception\LimitationException;
use Nekland\Woketo\Exception\MissingDataException;

class Message
{
    /**
     * It allows ~50MiB buffering as the default of Frame content is 0.5MB
     */
    const MAX_MESSAGES_BUFFERING = 100;

    /**
     * @var array|Frame[]
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

    /**
     * @see Message::setConfig() for full default configuration.
     *
     * @var array
     */
    private $config;

    public function __construct(array $config = [])
    {
        $this->frames = [];
        $this->isComplete = false;
        $this->buffer = '';
        $this->setConfig($config);
    }

    /**
     * Add some data to the buffer.
     *
     * @param $data
     */
    public function addBuffer($data)
    {
        $this->buffer .= $data;
    }

    /**
     * Clear the buffer.
     */
    public function clearBuffer()
    {
        $this->buffer = '';
    }

    /**
     * Get data inside the buffer.
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Remove data from the start of the buffer.
     *
     * @param Frame $frame
     * @return string
     */
    public function removeFromBuffer(Frame $frame) : string
    {
        $this->buffer = StringTools::removeStart($this->getBuffer(), $frame->getRawData(), '8bit');

        return $this->buffer;
    }

    /**
     * @param Frame $frame
     * @return Message
     * @throws \InvalidArgumentException
     * @throws LimitationException
     * @throws WrongEncodingException
     */
    public function addFrame(Frame $frame) : Message
    {
        if ($this->isComplete) {
            throw new \InvalidArgumentException('The message is already complete.');
        }

        if (count($this->frames) > $this->config['maxMessagesBuffering']) {
            throw new LimitationException(
                sprintf('We don\'t accept more than %s frames by message. This is a security limitation.', $this->config['maxMessagesBuffering'])
            );
        }

        $this->isComplete = $frame->isFinal();
        $this->frames[] = $frame;

        if ($this->isComplete()) {
            if (\in_array($this->getFirstFrame()->getOpcode(), [Frame::OP_CLOSE, Frame::OP_TEXT]) && !\mb_check_encoding($this->getContent(), 'UTF-8')) {
                throw new WrongEncodingException('The text is not encoded in UTF-8.');
            }
        }

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
            $res .= $frame->getContent();
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
        return \in_array($this->getFirstFrame()->getOpcode(), [Frame::OP_TEXT, Frame::OP_BINARY]);
    }

    /**
     * @return Frame[]
     */
    public function getFrames()
    {
        return $this->frames;
    }

    /**
     * @return bool
     */
    public function hasFrames()
    {
        return !empty($this->frames);
    }

    /**
     * @return int
     */
    public function countFrames() : int
    {
        return \count($this->frames);
    }

    /**
     * @param array $config
     * @return Message
     */
    public function setConfig(array $config = [])
    {
        $this->config = \array_merge([
            'maxMessagesBuffering' => Message::MAX_MESSAGES_BUFFERING
        ], $config);

        return $this;
    }
}
