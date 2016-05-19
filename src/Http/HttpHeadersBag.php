<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Ci-tron <dev@ci-tron.org>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Http;


class HttpHeadersBag
{
    private $headers;

    public function __construct()
    {
        $this->headers = [];
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function set(string $name, $value)
    {
        $name = strtolower($name);
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        $name = strtolower($name);

        return $this->headers[$name];
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function add(string $name, $value)
    {
        $name = strtolower($name);
        if (!empty($this->headers[$name])) {
            if (!is_array($this->headers[$name])){
                $this->headers[$name] = [$this->headers[$name]];
            }
        } else {
            $this->headers[$name] = [];
        }

        $this->headers[$name][] = $value;

        return $this;
    }
}
