<?php

namespace Skinny\DataObject;

/**
 * Description of Config
 *
 * @author Daro
 */
class Config extends ArrayWrapper {

    protected function getNameParts($name) {
        if (!is_string($name) || strlen($name) === 0) {
            return null;
        }

        $parts = explode('.', $name);
        array_walk($parts, function(&$value) {
            $value = trim($value);
        });
        return $parts;
    }

    protected function &getConfig($name, $default) {
        if (!array_key_exists($name, $this->_data)) {
            return $default;
        }

        $value = &$this->_data[$name];
        if (!is_array($value)) {
            return $value;
        }

        // todo
    }

    public function &get($name, $default = null) {
        $nameParts = $this->getNameParts($name);
        if (null === $nameParts) {
            return $default;
        }

        $firstLevel = parent::get($nameParts[0], $default);
        if (count($nameParts) === 1) {
            return $this->getConfig($firstLevel, $default);
        }

        // todo
    }

    public function set($name, $value) {
        $nameParts = $this->getNameParts($name);
        if (null === $nameParts) {
            return;
        }

        parent::set($name, $value);
    }

}
