<?php

/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */
declare(strict_types=1);

namespace Nekland\Woketo\Utils;

class BitManipulation
{
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
                sprintf('The given integer %s is not a byte.', $byte)
            );
        }

        if ($bitNumber < 1 || $bitNumber > 8) {
            throw new \InvalidArgumentException(
                sprintf('The bit number %s is not a correct value for a byte (1-8 required).', $bitNumber)
            );
        }

        $realNth = pow(2, 8 - $bitNumber);

        return (int) ($realNth === ($byte & $realNth));
    }

    /**
     * @param string $frame iso-8859-1 string
     * @param int $byteNumber
     * @return int
     */
    public static function nthByte($frame, int $byteNumber) : int
    {
        if (is_string($frame)) {
            $len = strlen($frame);

            if ($byteNumber < 1 || $byteNumber > $len) {
                throw new \InvalidArgumentException(
                    sprintf('The frame is only %s bytes larges but you tried to get the %sth byte.', $len, $byteNumber)
                );
            }

            return ord($frame[$byteNumber-1]);
        }

        if (is_int($frame)) {
            if ($frame < 0) {
                throw new \InvalidArgumentException(
                    sprintf('This method does not support negative ints as parameter for now. %s given.', $byteNumber)
                );
            }
            $hex = dechex($frame);
            $len = strlen($hex);

            // Index of the first octal of the wanted byte
            $realByteNth = ($byteNumber - 1) * 2;

            if ($byteNumber < 1 || ($realByteNth + 1) > $len) {
                throw new \InvalidArgumentException(
                    sprintf('Impossible to get the byte %s from the frame %s.', $byteNumber, $frame)
                );
            }


            return (hexdec($hex[$realByteNth]) << 4) + hexdec($hex[$realByteNth + 1]);
        }

        throw new \InvalidArgumentException(
            sprintf('The frame must be an int or string, %s given.', gettype($frame))
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
}
