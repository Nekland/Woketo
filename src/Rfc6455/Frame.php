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
use Nekland\Woketo\Utils\BitManipulation;

class Frame
{
    /**
     * The payload size can be specified on 64b unsigned int according to the RFC. That means that maximum data
     * inside the payload is 0b1111111111111111111111111111111111111111111111111111111111111111 bits. In
     * decimal and GB, that means 2147483647 GB. As this is a bit too much for the memory of your computer or
     * server, we specified a max size to
     *
     * @var int
     */
    private static $maxPayloadSize = 1024;

    private $rawData;
    private $frameSize;

    // Some cached data

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
     * @var int Number of bits representing the payload length in the current frame.
     */
    private $payloadLenSize;

    public function __construct($data)
    {
        $this->setRawData($data);
        $this->frameSize = strlen($data);
        if ($this->frameSize < 2) {
            throw new \InvalidArgumentException('Not enough data to be a frame.');
        }
    }

    public function setRawData($rawData)
    {
        $this->rawData = $rawData;
        $this->getInformationFromRawData();

        return $this;
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

    public function getMaskingKey() : string
    {
        if (!$this->isMasked()) {
            return '';
        }

        if (null === $this->payloadLenSize) {
            throw new \LogicException('The payload length size must be load before anything.');
        }

        // 8 is the numbers of bits before the payload len.
        $start = ((9 + $this->payloadLenSize) / 8) + 1;

        $value = BitManipulation::bytesFromTo($this->rawData, $start, $start + 3);

        return BitManipulation::intToString($value);
    }

    public function getPayload()
    {
        $infoBytesLen = (9 + $this->payloadLenSize) / 8 + ($this->isMasked() ? 4 : 0);
        if (strlen($this->rawData) < $infoBytesLen + $this->payloadLen) {
            throw new \LogicException(
                sprintf('Impossible to retrieve %s of payload when the full frame is %s bytes long.', $this->payloadLen, strlen($this->rawData))
            );
        }

        $payload = (string) substr($this->rawData, $infoBytesLen, $this->payloadLen);

        if ($this->isMasked()) {
            return $this->applyMask($payload);
        }

        return $payload;
    }

    public function getPayloadLength() : int
    {
        if (null !== $this->payloadLen) {
            return $this->payloadLen;
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
            $payloadLen = BitManipulation::bytesFromTo($this->rawData, 3, 11);
        }

        if ($payloadLen < 0 || $payloadLen > Frame::$maxPayloadSize) {
            throw new LimitationException;
        }

        return $this->payloadLen = $payloadLen;
    }

    public function isMasked() : bool
    {
        return (bool) BitManipulation::nthBit($this->secondByte, 1);
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
    }
}
