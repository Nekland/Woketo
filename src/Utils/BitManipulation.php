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

namespace Nekland\Woketo\Utils;
use Nekland\Woketo\Exception\Utils\NotLongEnoughException;

/**
 * Class BitManipulation
 *
 * Glossary:
 *   - in this context a "frame" is an assembly of bytes represented by a "byte-string" or a (signed) int.
 */
class BitManipulation
{
    /**
     * Mode from to is the default mode of inspection of frames. But PHP usually uses from and length to inspect frames.
     */
    const MODE_FROM_TO = 0;
    const MODE_PHP = 1;

    /**
     * Get a specific bit from a byte.
     *
     * @param int $byte
     * @param int $bitNumber
     * @return int
     */
    public static function nthBit(int $byte, int $bitNumber) : int
    {
        if ($byte < 0 || $byte > 255) {
            throw new \InvalidArgumentException(
                \sprintf('The given integer %s is not a byte.', $byte)
            );
        }

        if ($bitNumber < 1 || $bitNumber > 8) {
            throw new \InvalidArgumentException(
                \sprintf('The bit number %s is not a correct value for a byte (1-8 required).', $bitNumber)
            );
        }

        $realNth = \pow(2, 8 - $bitNumber);

        return (int) ($realNth === ($byte & $realNth));
    }

    /**
     * Get a specific byte inside a frame represented by an int or a string.
     *
     * @param string|int $frame      Non utf8 string (this should be more precisely a bytes-string).
     * @param int        $byteNumber Starting at 0.
     * @return int
     */
    public static function nthByte($frame, int $byteNumber) : int
    {
        if (\is_string($frame)) {
            $len = BitManipulation::frameSize($frame);

            if ($byteNumber < 0 || $byteNumber > ($len-1)) {
                throw new \InvalidArgumentException(
                    \sprintf('The frame is only %s bytes larges but you tried to get the %sth byte.', $len, $byteNumber)
                );
            }

            return \ord($frame[$byteNumber]);
        }

        if (\is_int($frame)) {
            if ($frame < 0) {
                throw new \InvalidArgumentException(
                    \sprintf('This method does not support negative ints as parameter for now. %s given.', $byteNumber)
                );
            }
            $hex = \dechex($frame);
            $len = BitManipulation::frameSize($hex);

            // Index of the first octal of the wanted byte
            $realByteNth = $byteNumber * 2;

            if ($byteNumber < 0 || ($realByteNth + 1) > $len) {
                throw new \InvalidArgumentException(
                    \sprintf('Impossible to get the byte %s from the frame %s.', $byteNumber, $frame)
                );
            }

            // Considering FF12AB (number) if you want the byte represented by AB you need to get the
            // first letter, shift it of 4 and add the next letter.
            // This may seems weird but that's because you read numbers from right to left.
            return (\hexdec($hex[$realByteNth]) << 4) + \hexdec($hex[$realByteNth + 1]);
            // _Notice that if the number is from right to left, your data is still from left to right_
        }

        throw new \InvalidArgumentException(
            \sprintf('The frame must be an int or string, %s given.', gettype($frame))
        );
    }

    public static function partOfByte(int $byte, int $part) : int
    {
        if ($byte < 0 || $byte > 255) {
            throw new \InvalidArgumentException(sprintf('%s is not a byte', $byte));
        }

        if ($part === 1) {
            return ($byte & 240) >> 4;
        }

        if ($part === 2) {
            return $byte & 15;
        }

        throw new \InvalidArgumentException(sprintf('A byte have only 2 parts. %s asked.', $part));
    }

    /**
     * Because strings are the best way to store many bytes in PHP it can
     * be useful to make the conversion between hex (which are strings)
     * array to string.
     *
     * @param array $hexArray
     * @return string
     */
    public static function hexArrayToString(...$hexArray) : string
    {
        if (\is_array($hexArray[0])) {
            $hexArray = $hexArray[0];
        }

        $res = '';
        foreach ($hexArray as $hexNum) {
            $res .= \chr(\hexdec($hexNum));
        }

        return $res;
    }

    /**
     * @param string|int $frame
     * @param int        $from        Byte where to start (should be inferior to $to).
     * @param int        $to          Byte where to stop (considering it starts at 0). The `to` value include the target
     *                                byte.
     * @param bool       $force8bytes By default PHP have a wrong behavior with 8 bytes variables. If you have 8 bytes
     *                                the returned int will be negative (because unsigned integers does not exists in PHP)
     * @return int
     */
    public static function bytesFromTo($frame, int $from, int $to, bool $force8bytes = false) : int
    {
        // No more than 64b (which return negative number when the first bit is specified)
        if (($to - $from) > 7 && (!$force8bytes && ($to - $from) !== 8)) {
            if ($force8bytes) {
                throw new \InvalidArgumentException(sprintf('Not more than 8 bytes (64bit) is supported by this method and you asked for %s bytes.', $to - $from));
            }
            throw new \InvalidArgumentException('PHP limitation: getting more than 7 bytes will return a negative number because unsigned int does not exist.');
        }

        if (\is_string($frame)) {
            if ((BitManipulation::frameSize($frame) - 1) < $to) {
                throw new NotLongEnoughException('The frame is not long enough.');
            }

            $subStringLength = $to - $from + 1;
            // Getting responsible bytes
            $subString = \substr($frame, $from, $subStringLength);
            $res = 0;

            // for each byte, getting ord
            for($i = 0; $i < $subStringLength; $i++) {
                $res <<= 8;
                $res += \ord($subString[$i]);
            }

            return $res;
        }

        if (!\is_int($frame)) {
            throw new \InvalidArgumentException(
                \sprintf('A frame can only be a string or int. %s given', gettype($frame))
            );
        }

        if ($frame < 0) {
            throw new \InvalidArgumentException('The frame cannot be a negative number');
        }

        $res = 0;
        for ($i = $from; $i <= $to; $i++) {
            $res <<= 8;
            $res += BitManipulation::nthByte($frame, $i);
        }

        return $res;
    }

    /**
     * Proxy to the substr to be sure to be use the right method (mb_substr)
     *
     * @param string $frame
     * @param int    $from
     * @param int    $to
     * @return string
     */
    public static function bytesFromToString(string $frame, int $from, int $to, int $mode = BitManipulation::MODE_FROM_TO) : string
    {
        if ($mode === BitManipulation::MODE_FROM_TO) {
            return \mb_substr($frame, $from, $to - $from + 1, '8bit');
        }

        return \mb_substr($frame, $from, $to, '8bit');
    }

    /**
     * Take a frame represented by a decimal int to transform it in a string.
     * Notice that any int is a frame and cannot be more than 8 bytes
     *
     * @param int      $frame
     * @param int|null $size  In bytes. This value should always be precise. Be careful if you don't !
     * @return string
     */
    public static function intToBinaryString(int $frame, int $size = null) : string
    {
        $format = 'J*';

        if ($size !== null) {
            switch (true) {
                case $size <= 2:
                    $format = 'n*';
                    break;
                case $size <= 4:
                    $format = 'N*';
                    break;
                case $size > 4:
                    $format = 'J*';
                    break;
            }
        }

        $res = \pack($format, $frame);

        if ($size === null) {
            $res = \ltrim($res, "\0");
        }

        return $res;
    }

    /**
     * Take an string frame and transform it to a decimal frame (inside an int).
     *
     * @param string $frame
     * @return int
     */
    public static function binaryStringtoInt(string $frame) : int
    {
        $len = BitManipulation::frameSize($frame);

        if ($len > 8) {
            throw new \InvalidArgumentException(
                \sprintf('The string %s cannot be converted to int because an int cannot be more than 8 bytes (64b).', $frame)
            );
        }

        if (\in_array(BitManipulation::frameSize($frame), [1, 3])) {
            $frame = "\0" . $frame;
        }

        switch(true) {
            case $len <= 2:
                $format = 'n';
                break;
            case $len <= 4:
                $format = 'N';
                break;
            default: // also known as "$len > 4" :)
                $format = 'J';

                do {
                    $frame = "\0" . $frame;
                } while (BitManipulation::frameSize($frame) !== 8);
        }

        return \unpack($format, $frame)[1];
    }

    /**
     * Method that return frame as hex (more readable).
     * Helpful for debug !
     *
     * @param string $frame
     * @return string
     */
    public static function binaryStringToHex(string $frame) : string
    {
        return \unpack('H*', $frame)[1];
    }

    /**
     * Haters gonna hate. `strlen` cannot be trusted because of an option of mbstring extension, more info:
     * http://php.net/manual/fr/mbstring.overload.php
     * http://php.net/manual/fr/function.mb-strlen.php#77040
     *
     * @param string $frame
     * @return int
     */
    public static function frameSize(string $frame) : int
    {
        if (\extension_loaded('mbstring')) {
            return \mb_strlen($frame, '8bit');
        }

        return \strlen($frame);
    }
}
