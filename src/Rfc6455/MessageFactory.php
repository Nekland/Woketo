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


class MessageFactory
{
    /**
     * Configuration for Message objects.
     * 
     * @var array
     */
    private $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @return Message
     */
    public function create()
    {
        return new Message($this->config);
    }
}
