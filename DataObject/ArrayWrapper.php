<?php

namespace Skinny\DataObject;

/**
 * Array Wrapper wraps an array so it can be used either as an array and also as an object.
 * This class contains methods for easy manipulating on items of the collection.
 *
 * @author Daro
 */
class ArrayWrapper extends DataBase implements \JsonSerializable, \ArrayAccess, \IteratorAggregate, \Countable {

    /**
     * Array wrapper constructor
     * @param array $array array to be wrapped
     */
    public function __construct(array &$array) {
        $this->_data = &$array;
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
        return $this->_data;
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
        $this->set($offset, $value);
    }

    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }

    public function getIterator() {
        return new \ArrayIterator($this->_data);
    }

    /**
     * Merge two arrays recursively
     *
     * Overwrite values with associative keys
     * Append values with integer keys
     *
     * @param array $arr1 First array
     * @param array $arr2 Second array
     *
     * @return array
     */
    public static function deepMerge(array $arr1, array $arr2) {
        if (empty($arr1)) {
            return $arr2;
        } else if (empty($arr2)) {
            return $arr1;
        }

        foreach ($arr2 as $key => $value) {
            if (is_int($key)) {
                $arr1[] = $value;
            } elseif (is_array($arr2[$key])) {
                if (!isset($arr1[$key])) {
                    $arr1[$key] = array();
                }

                if (is_int($key)) {
                    $arr1[] = static::deepMerge($arr1[$key], $value);
                } else {
                    $arr1[$key] = static::deepMerge($arr1[$key], $value);
                }
            } else {
                $arr1[$key] = $value;
            }
        }

        return $arr1;
    }

    public static function arrayValuesEqual(array $arr1, array $arr2) {
        sort($arr1);
        sort($arr2);
        return ($arr1 == $arr2);
    }

    public static function lastInsertKey(array $array) {
        end($array);
        $last_key = key($array);
        return $last_key;
    }

    public static function compareValues($value1, $value2) {
        if ($value1 > $value2) {
            return 1;
        }
        if ($value1 < $value2) {
            return -1;
        }
        return 0;
    }

    public static function combineWithKeys($keys, $values, $default = null) {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = key_exists($key, $values) ? $values[$key] : $default;
        }
        return $result;
    }

//    public static function forEachMultiDim(array $multidimensionalArray) {
//        return new MultidimensionalIterator($multidimensionalArray);
//    }

}

//class MultidimensionalIterator implements \Iterator {
//
//    public function __construct($array) {
//        ;
//    }
//
//    public function current() {
//        return 'abc';
//    }
//
//    public function key() {
//        return [1, 2, 'a'];
//    }
//
//    public function next() {
//        
//    }
//
//    public function rewind() {
//        
//    }
//
//    public function valid() {
//        return true;
//    }
//
//}
