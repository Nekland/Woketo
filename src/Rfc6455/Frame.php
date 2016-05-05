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

class Frame
{
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

    public function getMask() : string
    {
        if (!$this->isMasked()) {
            return '';
        }
        // TODO

        return $mask;
    }

    public function isMasked() : bool
    {
        return (bool) BitManipulation::nthBit($this->secondByte, 1);
    }

    private function getInformationFromRawData()
    {
        $this->firstByte = BitManipulation::nthByte($this->rawData, 1);
        $this->secondByte = BitManipulation::nthByte($this->rawData, 2);

        $this->final = (bool) BitManipulation::nthBit($this->firstByte, 1);
    }
}
