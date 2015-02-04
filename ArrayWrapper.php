<?php

namespace Skinny;

/**
 * Array Wrapper wraps an array so it can be used either as an array and also as an object.
 * This class contains methods for easy manipulating on items of the collection.
 *
 * @author Daro
 */
class ArrayWrapper implements \JsonSerializable, \ArrayAccess, \IteratorAggregate, \Countable {

    protected $_data;

    /**
     * Array wrapper constructor
     * @param array $array array to be wrapped
     */
    public function __construct(array &$array) {
        $this->_data = &$array;
    }

    /**
     * Magic isset function called by isseting member of the object.
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->_data[$name]);
    }

    /**
     * Magic get function called by getting member of the object.
     * Returns null if no item of given name is present.
     * @param string $name key name of the getting item
     * @return mixed
     */
    public function &__get($name) {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        $a = null;
        return $a;
        }

        return $null = null;
    }

    /**
     * Magic set function called by setting member of the object.
     * @param string $name key name of setting item
     * @param mixed $value value of the item
     */
    public function __set($name, $value) {
        $this->_data[$name] = $value;
    }

    /**
     * Magic unset function called by unsetting member of the object.
     * @param string $name key name of unsetting item
     */
    public function __unset($name) {
        unset($this->_data[$name]);
    }

    /**
     * Returns wheter object has no value.
     * @return bool
     */
    public function isEmpty() {
        return empty($this->_data);
    }

    /**
     * Clears object by removing all keys and items.
     * Preserves reference to a wrapped array.
     */
    public function clear() {
        foreach ($this->_data as $key => $value) {
            unset($this->_data[$key]);
        }
    }

    /**
     * Returns number of items.
     * @return int
     */
    public function count() {
        return count($this->_data);
    }

    /**
     * Returns number of items.
     * @return int
     */
    public function length() {
        return count($this->_data);
    }

    /**
     * Allows proper serialization to JavaScript Object Notation by returning wrapped array.
     * @return array
     */
    public function jsonSerialize() {
        return $this->_data;
    }

    /**
     * Returns reference to internal array which current instance wraps around.
     * @return array
     */
    public function &getWrappedArray() {
        return $this->_data;
    }

    /**
     * Converts object to array of values preserving key names.
     * @return array
     */
    public function toArray() {
        return clone $this->_data;
    }

    /**
     * Returns wheather array wrapper constains item at given key.
     * @param string $name name of checking key
     * @return mixed
     */
    public function contains($name) {
        return array_key_exists($name, $this->_data);
    }

    /**
     * Returns value of the item given by key name.
     * If object contains no item with given name, default value is returned.
     * @param string $name key name of getting item
     * @param mixed $default optional default value returned if item cannot be found
     * @return mixed
     */
    public function &get($name, $default = null) {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }

        return $default;
    }

    /**
     * Sets value to the item given by key name.
     * If the item does not exist it will be created.
     * @param string $name key name of setting item
     * @param mixed $value
     */
    public function set($name, $value) {
        $this->_data[$name] = $value;
    }

    /**
     * Removes from collection item given by the key name.
     * If the item does not exist method will do nothing.
     * @param string $name key name of removing item
     */
    public function remove($name) {
        unset($this->_data[$name]);
    }

    /**
     * Magic call function called by calling member of the object.
     * If the item does exist and is instance of Closure this method will call it with given arguments and return its value by reference.
     * Otherwise calling itemName($defaultValue) is shorthand of get('itemName', $defaultValue).
     * @param string $name key name of calling item
     * @param array $arguments arguments passed to calling Closure or default value of item getter
     * @return mixed
     */
    public function &__call($name, $arguments) {
        if (array_key_exists($name, $this->_data) && $this->_data[$name] instanceof \Closure) {
            $closure = $this->_data[$name];
            return call_user_func_array($closure, $arguments);
        }

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

}
