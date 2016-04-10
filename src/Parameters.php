<?php
/**
 * Created by PhpStorm.
 * User: shalom.s
 * Date: 20/03/16
 * Time: 1:24 PM
 */

namespace Http\Parameters;

class Parameters implements \IteratorAggregate, \Countable, \ArrayAccess
{
    protected $collection;

    /**
     * Parameters constructor.
     * @param $parameters
     */
    public function __construct(array $parameters)
    {
        $this->collection = $parameters;
    }

    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->collection[$key];
        }

        return $default;
    }

    public function add($key, $value)
    {
        $this->collection[$key] = $value;
    }

    public static function each(array $array, \Closure $callback)
    {
        $collection = [];
        foreach($array as $key => $val)
        {
            $collection[] = call_user_func($callback, $key, $val);
        }

        return new static($collection);
    }

    public static function find(array $array, \Closure $callback, $default = null)
    {
        foreach ($array as $key => $val)
        {
            if (call_user_func($callback, $key, $val) === true) {
                return $val;
            }
        }

        return $default;
    }

    public function map(\Closure $callback, $default = null)
    {
        $array = $this->collection;
        foreach ($array as $key => $val)
        {
            if (call_user_func($callback, $key, $val) === true) {
                return $val;
            }
        }

        return $default;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->collection);
    }

    public function count()
    {
        return count($this->collection);
    }

    public function offsetSet($offset, $value)
    {
        $this->collection[$offset] = $value;
    }

    public function offsetGet($offset)
    {
        return $this->collection[$offset];
    }

    public function offsetUnset($offset)
    {
        unset($this->collection[$offset]);
    }

    public function offsetExists($offset)
    {
        return isset($this->collection[$offset]);
    }
}