<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Utils;


use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class SimpleLogger extends AbstractLogger
{
    /**
     * Modify the log behavior
     * @var bool
     */
    private $debug;

    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Log
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        // Doesn't log everything in not debug context
        if ($this->debug || \in_array($level, [LogLevel::CRITICAL, LogLevel::ERROR])) {
            echo '[' . date('Y-m-d H:i:s') . '][' . $level . '] ' . $message ."\n";
        }
    }
}
