<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Skinny;

/**
 * Description of Enum
 *
 * @author Daro
 */
abstract class Enum {

    public static function getValues() {
        $reflection = new \ReflectionClass(get_called_class());
        return $reflection->getConstants();
    }

    public static function isValidName($name, $strict = false) {
        $values = static::getValues();

        if ($strict) {
            return array_key_exists($name, $values);
        }

        $keys = array_map('strtolower', array_keys($values));
        return in_array(strtolower($name), $keys);
    }

    public static function isValidValue($value) {
        $values = array_values(self::getValues());
        return in_array($value, $values, true);
    }

    public static function get($name, $strict = false) {
        $values = static::getValues();

        if (!$strict) {
            $name = strtolower($name);
            $values = array_change_key_case($values);
        }

        return isset($values[$name]) ? $values[$name] : null;
    }

}
