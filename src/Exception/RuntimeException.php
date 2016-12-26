<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Exception;

class RuntimeException extends \Exception
{
    public function __construct($e)
    {
        if ($e instanceof \Exception) {
            parent::__construct('Bad behavior of inside of woketo. Error: (' . get_class($e) . ') ' . $e->getMessage());
        } else {
            parent::__construct($e);
        }
    }
}
