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


class HttpHeadersBag implements \ArrayAccess
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
            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
        
        return $this;
    }

    /**
     * @param string $name
     */
    public function remove(string $name)
    {
        $name = strtolower($name);
        unset($this->headers[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $offset = strtolower($offset);

        return isset($this->headers[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception('You must use either add or set method to update the header bag.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }
}
