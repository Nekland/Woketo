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
use Nekland\Woketo\Exception\Frame\InvalidFrameException;
use Nekland\Woketo\Exception\Frame\TooBigFrameException;
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
     * The payload size can be specified on 64b unsigned int according to the RFC. That means that maximum data
     * inside the payload is 0b1111111111111111111111111111111111111111111111111111111111111111 bits. In
     * decimal and GB, that means 2147483647 GB. As this is a bit too much for the memory of your computer or
     * server, we specified a max size to.
     *
     * Notice that to support larger transfer we need to implemente a cache strategy on the harddrive. It also suggest
     * to have a threaded environment as the task of retrieving the data and treat it will be long.
     *
     * This value is in bytes. Here we allow 1MiB.
     *
     * @var int
     */
    private static $maxPayloadSize = 1049000;

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
     * @var string
     */
    private $infoBytesLen;

    public function __construct($data=null)
    {
        if (null !== $data) {
            $this->setRawData($data);
            $this->checkFrameSize();
        }
    }

    /**
     * @param string|int $rawData Probably more likely a string than an int, but well... why not.
     * @return self
     * @throws InvalidFrameException
     */
    public function setRawData($rawData)
    {
        $this->rawData = $rawData;
        $this->frameSize = strlen($rawData);

        if ($this->frameSize < 2) {
            throw new InvalidFrameException('Not enough data to be a frame.');
        }
        $this->getInformationFromRawData();

        return $this;
    }

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
            // As the payload cannot be bigger than self::$maxPayloadSize. It will always be less than 65535
            // and so will never take more than 2 bytes.
            $firstLen = 126;
            $secondLen = $this->payloadLen;
        }

        $data .= BitManipulation::intToString(
            ((((null === $this->final ? 1 : (int) $this->final) << 7) + $this->opcode) << 8)
            + ($this->isMasked() << 7) + $firstLen
        );
        if (null !== $secondLen) {
            $data .= BitManipulation::intToString($secondLen);
        }
        if ($this->isMasked()) {
            $data .= $this->getMaskingKey();
            $data .= $this->applyMask($this->getPayload());
            
            return $data;
        }
        
        return $data . $this->getPayload();
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
        return BitManipulation::nthBit($this->firstByte, 2);
    }

    /**
     * @return boolean
     */
    public function getRsv2() : bool
    {
        return BitManipulation::nthBit($this->firstByte, 3);
    }

    /**
     * @return boolean
     */
    public function getRsv3() : bool
    {
        return BitManipulation::nthBit($this->firstByte, 4);
    }

    /**
     * @return int
     */
    public function getOpcode() : int
    {
        return BitManipulation::partOfByte($this->firstByte, 2);
    }
    
    public function setOpcode(int $opcode) : Frame
    {
        if (!in_array($opcode, [Frame::OP_TEXT, Frame::OP_BINARY, Frame::OP_CLOSE, Frame::OP_CONTINUE, Frame::OP_PING, Frame::OP_PONG])) {
            throw new \InvalidArgumentException('Wrong opcode !');
        }
        
        $this->opcode = $opcode;
        
        return $this;
    }

    public function setMaskingKey(string $mask) : Frame
    {
        if (null === $mask) {
            $this->isMasked();
        }
        $this->mask = $mask;

        return $this;
    }

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
        $start = ((9 + $this->payloadLenSize) / 8) + 1;

        $value = BitManipulation::bytesFromTo($this->rawData, $start, $start + 3);

        return $this->mask = BitManipulation::intToString($value);
    }

    public function getPayload()
    {
        if ($this->payload) {
            return $this->payload;
        }

        $this->checkFrameSize();

        $infoBytesLen = $this->getInfoBytesLen();
        $payload = (string) substr($this->rawData, $infoBytesLen, $this->payloadLen);

        if ($this->isMasked()) {
            return $this->payload = $this->applyMask($payload);
        }

        return $this->payload = $payload;
    }

    public function getInfoBytesLen()
    {
        if ($this->infoBytesLen) {
            return $this->infoBytesLen;
        }

        // Calculate headers (infos) length
        // which can depend on mask and payload length information size
        return $this->infoBytesLen = (9 + $this->payloadLenSize) / 8 + ($this->isMasked() ? 4 : 0);
    }

    public function checkFrameSize()
    {
        $infoBytesLen = $this->getInfoBytesLen();
        $realDataLength = strlen($this->rawData);
        $theoricDataLength = $infoBytesLen + $this->payloadLen;
        if ($realDataLength < $theoricDataLength) {
            throw new IncompleteFrameException(
                sprintf('Impossible to retrieve %s bytes of payload when the full frame is %s bytes long.', $theoricDataLength, $realDataLength)
            );
        }
        if ($realDataLength > $theoricDataLength) {
            throw new TooBigFrameException();
        }
    }
    
    public function setPayload(string $payload) : Frame
    {
        $this->payload = $payload;
        $this->payloadLen = strlen($this->payload);
        $this->payloadLenSize = 7;
        
        if ($this->payloadLen > 126) {
            $this->payloadLenSize += 16;
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

        if ($payloadLen === 126) {
            $this->payloadLenSize += 16;
            $payloadLen = BitManipulation::bytesFromTo($this->rawData, 3, 4);
        }

        if ($payloadLen === 127) {
            $this->payloadLenSize += 48;

            $payloadLen = BitManipulation::bytesFromTo($this->rawData, 3, 11, true);
        }

        // Check < 0 because 64th bit is the negative one in PHP.
        if ($payloadLen < 0 || $payloadLen > Frame::$maxPayloadSize) {
            throw new TooBigFrameException;
        }

        return $this->payloadLen = $payloadLen;
    }

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

    public function applyMask(string $payload) : string
    {
        $res = '';
        $mask = $this->getMaskingKey();


        for ($i = 0; $i < $this->payloadLen; $i++) {
            $payloadByte = $payload[$i];
            $res .= $payloadByte ^ $mask[$i % 4];
        }

        return $res;
    }

    private function getInformationFromRawData()
    {
        $this->firstByte = BitManipulation::nthByte($this->rawData, 1);
        $this->secondByte = BitManipulation::nthByte($this->rawData, 2);

        $this->final = (bool) BitManipulation::nthBit($this->firstByte, 1);
        $this->payloadLen = $this->getPayloadLength();

        Frame::checkFrame($this);
    }

    public static function checkFrame(Frame $frame)
    {
        if ($frame->getOpcode() === Frame::OP_TEXT && !mb_check_encoding($frame->getPayload())) {
            throw new InvalidFrameException('The text is not encoded in UTF-8.');
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
}
