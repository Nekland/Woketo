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


use Nekland\Woketo\Exception\Frame\TooBigFrameException;
use Nekland\Woketo\Exception\MissingDataException;
use Nekland\Woketo\Utils\BitManipulation;

class FrameGenerator
{
    public function getRawFrame(Frame $frame)
    {

    }

    public function buildFrame(Frame $frame)
    {
        if ($data = $frame->getRawData() === null) {
            throw new \RuntimeException('Impossible to build a frame that have no raw data.');
        }

        if ($frame->getFrameSize() < 2) {
            // This suppose the data is only 1 byte long (which is stupid). So maybe we should throw a websocket
            // exception that break the process of frame creation ?
            throw new MissingDataException('A frame of less than 2 bytes does not exists.');
        }

        $firstByte = BitManipulation::nthByte($data, 0);
        $secondByte = BitManipulation::nthByte($data, 1);

        $final = (bool) BitManipulation::nthBit($firstByte, 1);

        // Getting payload len
        list($payloadLen, $payloadLenSize) = $this->getPayloadLength($data, $secondByte);

        // Getting mask
        $isMasked = (bool) BitManipulation::nthBit($secondByte, 1);
        if ($isMasked) {
            // 8 is the numbers of bits before the payload len.
            $maskingKeyStart = ((9 + $payloadLenSize) / 8);
            $maskingKey = BitManipulation::bytesFromTo($data, $maskingKeyStart, $maskingKeyStart + 3);
            $frame->setMaskingKey(BitManipulation::intToString($maskingKey));
        }

        $frame->setFinal($final);
    }

    public function getPayloadLength(string $rawData, int $secondByte = null)
    {
        if ($secondByte === null) {
            $secondByte = BitManipulation::nthByte($rawData, 1);
        }

        // Get the first part of the payload length by removing mask information from the second byte
        $payloadLen = $secondByte & 127;
        $payloadLenSize = 7;

        if ($payloadLen === 126) {
            $payloadLenSize += 16;
            $payloadLen = BitManipulation::bytesFromTo($rawData, 2, 3);
        } else if ($payloadLen === 127) {
            $payloadLenSize += 64;

            $payloadLen = BitManipulation::bytesFromTo($rawData, 2, 9, true);
        }

        // Check < 0 because 64th bit is the negative one in PHP.
        if ($payloadLen < 0 || $payloadLen > Frame::$maxPayloadSize) {
            throw new TooBigFrameException;
        }

        return [$payloadLen, $payloadLenSize];
    }
}
