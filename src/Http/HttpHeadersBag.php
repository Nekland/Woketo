<?php
/**
 * This file is a part of Woketo package.
 *
 * (c) Nekland <dev@nekland.fr>
 *
 * For the full license, take a look to the LICENSE file
 * on the root directory of this project
 */

namespace Nekland\Woketo\Http;


/**
 * Class HttpHeadersBag
 *
 * @internal
 */
class HttpHeadersBag implements \ArrayAccess, \Iterator
{
    /**
     * @var string[]
     */
    private $headers;

    /**
     * Is used by the iterator feature to store a possible "false" value when the loop over the array is finished.
     *
     * @var mixed
     */
    private $next;

    public function __construct(array $headers = null)
    {
        $this->headers = $headers ?: [];
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function set(string $name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        $headersLower = \array_change_key_case($this->headers);
        $name = \strtolower($name);

        return isset($headersLower[$name]) ? $headersLower[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @return self
     */
    public function add(string $name, $value)
    {
        if (!empty($this->headers[$name])) {
            if (!\is_array($this->headers[$name])){
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
        unset($this->headers[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
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

    public function current()
    {
        return $this->next ?: \current($this->headers);
    }

    public function next()
    {
        $this->next = \next($this->headers);
    }

    public function key()
    {
        return \key($this->headers);
    }

    public function valid()
    {
        return $this->next !== false;
    }

    public function rewind()
    {
        $this->current = 0;
    }
}
