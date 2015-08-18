<?php

namespace Skinny\DataObject;

/**
 * Base class for data objects based on array.
 *
 * @author Daro
 */
class DataBase {

    protected $_data = [];

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
     * Returns wheather array wrapper constains item at given key.
     * @param string $name name of checking key
     * @return mixed
     */
    public function contains($name) {
        return array_key_exists($name, $this->_data);
    }

}
