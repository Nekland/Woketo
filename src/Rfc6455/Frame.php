<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

declare(strict_types=1);
namespace Nekland\Woketo\Rfc6455;

use Nekland\Woketo\Exception\Frame\ControlFrameException;
use Nekland\Woketo\Exception\Frame\IncompleteFrameException;
use Nekland\Woketo\Exception\Frame\InvalidFrameException;
use Nekland\Woketo\Exception\Frame\ProtocolErrorException;
use Nekland\Woketo\Exception\Frame\TooBigControlFrameException;
use Nekland\Woketo\Exception\Frame\TooBigFrameException;
use Nekland\Woketo\Exception\Utils\NotLongEnoughException;
use Nekland\Woketo\Utils\BitManipulation;

/**
 * Class Frame
 *
 * @TODO: add support for extensions.
 */
class Frame
{
    const OP_CONTINUE =  0;
    const OP_TEXT     =  1;
    const OP_BINARY   =  2;
    const OP_CLOSE    =  8;
    const OP_PING     =  9;
    const OP_PONG     = 10;

    // To understand codes, please refer to RFC:
    // https://tools.ietf.org/html/rfc6455#section-7.4
    const CLOSE_NORMAL                  = 1000;
    const CLOSE_GOING_AWAY              = 1001;
    const CLOSE_PROTOCOL_ERROR          = 1002;
    const CLOSE_WRONG_DATA              = 1003;
    // 1004-1006 are reserved
    const CLOSE_INCOHERENT_DATA         = 1007;
    const CLOSE_POLICY_VIOLATION        = 1008;
    const CLOSE_TOO_BIG_TO_PROCESS      = 1009;
    const CLOSE_MISSING_EXTENSION       = 1010; // In this case you should precise a reason
    const CLOSE_UNEXPECTING_CONDITION   = 1011;
    // 1015 is reserved

    /**
     * @see https://tools.ietf.org/html/rfc6455#section-5.5
     */
    const MAX_CONTROL_FRAME_SIZE = 125;

    /**
     * The payload size can be specified on 64b unsigned int according to the RFC. That means that maximum data
     * inside the payload is 0b1111111111111111111111111111111111111111111111111111111111111111 bits. In
     * decimal and GB, that means 2147483647 GB. As this is a bit too much for the memory of your computer or
     * server, we specified a max size to.
     *
     * Notice that to support larger transfer we need to implemente a cache strategy on the harddrive. It also suggest
     * to have a threaded environment as the task of retrieving the data and treat it will be long.
     *
     * This value is in bytes. Here we allow 0.5 MiB.
     *
     * @var int
     */
    private static $defaultMaxPayloadSize = 524288;

    /**
     * Complete string representing data collected from socket
     *
     * @var string
     */
    private $rawData;

    /**
     * @var int
     */
    private $frameSize;

    // In case of enter request the following data is cache.
    // Otherwise it's data used to generate "rawData".

    /**
     * @var int
     */
    private $firstByte;

    /**
     * @var int
     */
    private $secondByte;

    /**
     * @var bool
     */
    private $final;

    /**
     * @var int
     */
    private $payloadLen;

    /**
     * Number of bits representing the payload length in the current frame.
     *
     * @var int
     */
    private $payloadLenSize;

    /**
     * Cache variable for the payload.
     *
     * @var string
     */
    private $payload;

    /**
     * @var
     */
    private $mask;

    /**
     * @var int
     */
    private $opcode;

    /**
     * @var int
     */
    private $infoBytesLen;

    /**
     * @see Frame::setConfig() for full default configuration.
     *
     * @var array
     */
    private $config;

    /**
     * You should construct this object by using the FrameFactory class instead of this direct constructor.
     *
     * @param string|null $data
     * @param array       $config
     * @internal
     */
    public function __construct(string $data = null, array $config = [])
    {
        $this->setConfig($config);
        if (null !== $data) {
            $this->setRawData($data);
        }
    }

    /**
     * It also run checks on data.
     *
     * @param string|int $rawData Probably more likely a string than an int, but well... why not.
     * @return self
     * @throws IncompleteFrameException
     */
    public function setRawData(string $rawData)
    {
        $this->rawData = $rawData;
        $this->frameSize = BitManipulation::frameSize($rawData);

        if ($this->frameSize < 2) {
            throw new IncompleteFrameException('Not enough data to be a frame.');
        }
        $this->getInformationFromRawData();

        try {
            $this->checkFrameSize();
        } catch (TooBigFrameException $e) {
            $this->frameSize = $e->getMaxLength();
            $this->rawData = BitManipulation::bytesFromToString($this->rawData, 0, $this->frameSize, BitManipulation::MODE_PHP);
        }

        Frame::checkFrame($this);

        return $this;
    }

    /**
     * Return cached raw data or generate it from data (payload/frame type).
     *
     * @return string
     * @throws InvalidFrameException
     */
    public function getRawData() : string
    {
        if (null !== $this->rawData) {
            return $this->rawData;
        }

        if (!$this->isValid()) {
            throw new InvalidFrameException('The frame you composed is not valid !');
        }
        $data = '';

        $secondLen = null;
        if ($this->payloadLen < 126) {
            $firstLen = $this->payloadLen;

        } else {
            if ($this->payloadLen < 65536) {
                $firstLen = 126;
            } else {
                $firstLen = 127;
            }
            $secondLen = $this->payloadLen;
        }

        $data .= BitManipulation::intToBinaryString(
            ((((null === $this->final ? 1 : (int) $this->final) << 7) + $this->opcode) << 8)
            + ($this->isMasked() << 7) + $firstLen
        );
        if (null !== $secondLen) {
            $data .= BitManipulation::intToBinaryString($secondLen, $firstLen === 126 ? 2 : 8);
        }
        if ($this->isMasked()) {
            $data .= $this->getMaskingKey();
            $data .= $this->applyMask();

            return $this->rawData = $data;
        }

        return $this->rawData = $data . $this->getPayload();
    }

    /**
     * As a message is composed by many frames, the frame have the information of "last" or not.
     * The frame is final if the first bit is 0.
     */
    public function isFinal() : bool
    {
        return $this->final;
    }

    /**
     * @param bool $final
     * @return Frame
     */
    public function setFinal(bool $final) : Frame
    {
        $this->final = $final;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getRsv1() : bool
    {
        return (bool) BitManipulation::nthBit($this->firstByte, 2);
    }

    /**
     * @return boolean
     */
    public function getRsv2() : bool
    {
        return (bool) BitManipulation::nthBit($this->firstByte, 3);
    }

    /**
     * @return boolean
     */
    public function getRsv3() : bool
    {
        return (bool) BitManipulation::nthBit($this->firstByte, 4);
    }

    /**
     * @return int
     */
    public function getOpcode() : int
    {
        return BitManipulation::partOfByte($this->firstByte, 2);
    }

    /**
     * Set the opcode of the frame.
     *
     * @param int $opcode
     * @return Frame
     */
    public function setOpcode(int $opcode) : Frame
    {
        if (!\in_array($opcode, [Frame::OP_TEXT, Frame::OP_BINARY, Frame::OP_CLOSE, Frame::OP_CONTINUE, Frame::OP_PING, Frame::OP_PONG])) {
            throw new \InvalidArgumentException('Wrong opcode !');
        }

        $this->rawData = null;
        $this->opcode = $opcode;

        return $this;
    }

    /**
     * Set the masking key of the frame. As a consequence the frame is now considered as masked.
     *
     * @param string $mask
     * @return Frame
     */
    public function setMaskingKey(string $mask) : Frame
    {
        if (null === $mask) {
            $this->isMasked();
        }
        $this->mask = $mask;
        $this->rawData = null;

        return $this;
    }

    /**
     * Get the masking key (from cache if possible).
     *
     * @return string
     */
    public function getMaskingKey() : string
    {
        if (null !== $this->mask) {
            return $this->mask;
        }
        if (!$this->isMasked()) {
            return '';
        }

        if (null === $this->payloadLenSize) {
            throw new \LogicException('The payload length size must be load before anything.');
        }

        // 8 is the numbers of bits before the payload len.
        $start = ((9 + $this->payloadLenSize) / 8);

        $value = BitManipulation::bytesFromTo($this->rawData, $start, $start + 3);

        return $this->mask = BitManipulation::intToBinaryString($value, 4);
    }

    /**
     * Get payload from cache or generate it from raw data.
     *
     * @return string
     */
    public function getPayload()
    {
        if ($this->payload !== null) {
            return $this->payload;
        }

        $infoBytesLen = $this->getInfoBytesLen();
        $payload = (string) BitManipulation::bytesFromToString($this->rawData, $infoBytesLen, $this->payloadLen, BitManipulation::MODE_PHP);

        if ($this->isMasked()) {
            $this->payload = $payload;

            return $this->payload = $this->applyMask();
        }

        return $this->payload = $payload;
    }

    /**
     * Returns the content and not potential metadata of the body.
     * If you want to get the real body you will prefer using `getPayload`
     *
     * @return string
     */
    public function getContent()
    {
        $payload = $this->getPayload();
        if ($this->getOpcode() === Frame::OP_TEXT || $this->getOpcode() === Frame::OP_BINARY) {
            return $payload;
        }

        $len = BitManipulation::frameSize($payload);
        if ($len !== 0 && $this->getOpcode() === Frame::OP_CLOSE) {
            return BitManipulation::bytesFromToString($payload, 2, $len);
        }

        return $payload;
    }

    /**
     * Get length of meta data of the frame.
     * Metadata contains type of frame, length, masking key and rsv data.
     *
     * @return int
     */
    public function getInfoBytesLen()
    {
        if ($this->infoBytesLen) {
            return $this->infoBytesLen;
        }

        // Calculate headers (infos) length
        // which can depend on mask and payload length information size
        return $this->infoBytesLen = (9 + $this->payloadLenSize) / 8 + ($this->isMasked() ? 4 : 0);
    }

    /**
     * Set the payload.
     * The raw data is reset.
     *
     * @param string $payload
     * @return Frame
     */
    public function setPayload(string $payload) : Frame
    {
        $this->rawData = null;
        $this->payload = $payload;
        $this->payloadLen = BitManipulation::frameSize($this->payload);
        $this->payloadLenSize = 7;

        if ($this->payloadLen > 126 && $this->payloadLen < 65536) {
            $this->payloadLenSize += 16;
        } else if ($this->payloadLen > 126) {
            $this->payloadLenSize += 64;
        }

        return $this;
    }

    /**
     * @return int
     * @throws TooBigFrameException
     */
    public function getPayloadLength() : int
    {
        if (null !== $this->payloadLen) {
            return $this->payloadLen;
        }

        if ($this->secondByte === null) {
            throw new \RuntimeException('Impossible to get the payload length at this state of the frame, there is no data.');
        }

        // Get the first part of the payload length by removing mask information from the second byte
        $payloadLen = $this->secondByte & 127;
        $this->payloadLenSize = 7;

        try {
            if ($payloadLen === 126) {
                $this->payloadLenSize += 16;
                $payloadLen = BitManipulation::bytesFromTo($this->rawData, 2, 3);
            } else if ($payloadLen === 127) {
                $this->payloadLenSize += 64;

                $payloadLen = BitManipulation::bytesFromTo($this->rawData, 2, 9, true);
            }

            // Check < 0 because 64th bit is the negative one in PHP.
            if ($payloadLen < 0 || $payloadLen > $this->config['maxPayloadSize']) {
                throw new TooBigFrameException($this->config['maxPayloadSize']);
            }

            return $this->payloadLen = $payloadLen;
        } catch (NotLongEnoughException $e) {
            throw new IncompleteFrameException('Impossible to determine the length of the frame because message is too small.');
        }
    }

    /**
     * If there is a mask in the raw data or if the mask was set, the frame is masked and this method gets the result
     * for you.
     *
     * @return bool
     */
    public function isMasked() : bool
    {
        if ($this->mask !== null) {
            return true;
        }

        if ($this->rawData !== null) {
            return (bool) BitManipulation::nthBit($this->secondByte, 1);
        }

        return false;
    }

    /**
     * This method works for mask and unmask (it's the same operation)
     *
     * @return string
     */
    public function applyMask() : string
    {
        $res = '';
        $mask = $this->getMaskingKey();

        for ($i = 0; $i < $this->payloadLen; $i++) {
            $payloadByte = $this->payload[$i];
            $res .= $payloadByte ^ $mask[$i % 4];
        }

        return $res;
    }

    /**
     * Fill metadata of the frame from the raw data.
     */
    private function getInformationFromRawData()
    {
        $this->firstByte = BitManipulation::nthByte($this->rawData, 0);
        $this->secondByte = BitManipulation::nthByte($this->rawData, 1);

        $this->final = (bool) BitManipulation::nthBit($this->firstByte, 1);
        $this->payloadLen = $this->getPayloadLength();
    }

    /**
     * Check if the frame have the good size based on payload size.
     *
     * @throws IncompleteFrameException
     * @throws TooBigFrameException
     */
    public function checkFrameSize()
    {
        $infoBytesLen = $this->getInfoBytesLen();
        $this->frameSize = BitManipulation::frameSize($this->rawData);
        $theoricDataLength = $infoBytesLen + $this->payloadLen;

        if ($this->frameSize < $theoricDataLength) {
            throw new IncompleteFrameException(
                sprintf('Impossible to retrieve %s bytes of payload when the full frame is %s bytes long.', $theoricDataLength, $this->frameSize)
            );
        }

        if ($this->frameSize > $theoricDataLength) {
            throw new TooBigFrameException($theoricDataLength);
        }

        if ($this->getOpcode() === Frame::OP_CLOSE && $this->payloadLen === 1) {
            throw new ProtocolErrorException('The close frame cannot be only 1 bytes as the close code MUST be send as 2 bytes unsigned int.');
        }
    }


    /**
     * Validate a frame with RFC criteria
     *
     * @param Frame $frame
     *
     * @throws ControlFrameException
     * @throws TooBigControlFrameException
     */
    public static function checkFrame(Frame $frame)
    {
        if ($frame->isControlFrame()) {
            if (!$frame->isFinal()) {
                throw new ControlFrameException('The frame cannot be fragmented');
            }

            if ($frame->getPayloadLength() > Frame::MAX_CONTROL_FRAME_SIZE) {
                throw new TooBigControlFrameException('A control frame cannot be larger than 125 bytes.');
            }
        }
    }

    /**
     * You can call this method to be sure your frame is valid before trying to get the raw data.
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return !empty($this->opcode);
    }

    /**
     * The Control Frame is a pong, ping, close frame or a reserved frame between 0xB-0xF.
     * @see https://tools.ietf.org/html/rfc6455#section-5.5
     *
     * @return bool
     */
    public function isControlFrame()
    {
        return $this->getOpcode() >= 8;
    }

    /**
     * @param array $config
     * @return Frame
     */
    public function setConfig(array $config = [])
    {
        $this->config = \array_merge([
            'maxPayloadSize' => Frame::$defaultMaxPayloadSize,
        ],$config);

        return $this;
    }
}
