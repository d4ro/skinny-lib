<?php

namespace Skinny;

/**
 * Description of ArrayWrapper
 *
 * @author Daro
 */
class ArrayWrapper implements \JsonSerializable, \ArrayAccess, \IteratorAggregate, \Traversable, \Countable {

    protected $_data;

    public function __construct(array &$array) {
        $this->_data = $array;
    }

    public function __isset($name) {
        return isset($this->_data[$name]);
    }

    public function &__get($name) {
        if (isset($this->_data[$name]))
            return $this->_data[$name];
        return null;
    }

    public function __set($name, $value) {
        $this->_data[$name] = $value;
    }

    public function __unset($name) {
        unset($this->_data[$name]);
    }

    public function isEmpty() {
        return empty($this->_data);
    }

    public function length() {
        return count($this->_data);
    }

    public function jsonSerialize() {
        return $this->_data;
    }

    public function toArray() {
        return clone $this->_data;
    }

    public function get($name, $default) {
        if (isset($this->_data[$name]))
            return $this->_data[$name];

        return $default;
    }

    public function set($name, $value) {
        $this->_data[$name] = $value;
    }

    public function unset_($name) {
        unset($this->_data[$name]);
    }

    public function __call($name, $arguments) {
        $default = isset($arguments[0]) ? $arguments[0] : null;
        return $this->get($name, $default);
    }

    public function push($value) {
        return array_push($this->_data, $value);
    }

    public function pop() {
        return array_pop($this->_data);
    }

    public function shift() {
        return array_shift($this->_data);
    }

    public function unshift($value) {
        return array_unshift($this->_data, $value);
    }

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->_data);
    }

    public function offsetGet($offset) {
        return $this->get($offset, null);
    }

    public function offsetSet($offset, $value) {
        $this->_data[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }

    public function getIterator() {
        return new \ArrayIterator($this->_data);
    }

    public function count() {
        return count($this->_data);
    }

}