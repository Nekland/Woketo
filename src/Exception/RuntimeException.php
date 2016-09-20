<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Exception;

class RuntimeException extends \Exception
{
    public function __construct(\Exception $e)
    {
        parent::__construct('Bad behavior of inside of woketo. Error: (' . get_class($e) . ') ' . $e->getMessage());
    }
}
