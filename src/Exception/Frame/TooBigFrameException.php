<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Exception\Frame;

use Exception;
use Nekland\Woketo\Exception\LimitationException;

class TooBigFrameException extends LimitationException
{
    /**
     * @var int
     */
    private $maxLength;

    /**
     * @param int $maxLength
     * @param string $message
     */
    public function __construct(int $maxLength, string $message = 'The frame is too big to be processed.')
    {
        parent::__construct($message, null, null);
        $this->maxLength = $maxLength;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }
}
