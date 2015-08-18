<?php

namespace Skinny;

/**
 * Description of Url
 *
 * @author Daro
 */
class Url {

    public static function combine($url1, $url2 = '') {
        if (is_array($url1)) {
            $url1 = call_user_func_array(array(__CLASS__, __METHOD__), $url1);
        }

        if (is_array($url2)) {
            $url2 = call_user_func_array(array(__CLASS__, __METHOD__), $url2);
        }

        if (empty($url1)) {
            return $url2;
        }

        if (empty($url2)) {
            return $url1;
        }

        if (self::isAbsolute($url2)) {
            return $url2;
        }

        $path = $url1 . ($url1[strlen($url1) - 1] == '/' ? '' : '/') . ltrim($url2, '/');

        $args = func_get_args();
        if (count($args) > 2) {
            array_shift($args);
            // TODO: sprawdzić, czy nie potrzeba zamiast 0 dać key($args)
            $args[0] = $path;
            $path = call_user_func_array(array(__CLASS__, __METHOD__), $args);
        }

        return $path;
    }
    
    /**
     * Sprawdza czy na początku url'a znajduje się protokół.
     * 
     * @param string $url
     * @return boolean
     */
    public static function hasProtocol($url) {
        return (bool) preg_match("~^(?:f|ht)tps?://~i", $url);
    }

    public static function isAbsolute($url) {
        return (bool) preg_match("/^(?:((?:https?|ftp):)?\/\/)/i", $url);
    }

    public static function isCorrect($url) {
        // TODO: sprawdzić poprawność algorytmu
        // TODO: ERROR - url nie jest poprawny dla zapisu: "//onet.pl"
        return (bool) preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url);
    }

}
