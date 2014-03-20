<?php

namespace Skinny;

/**
 * Description of Url
 *
 * @author Daro
 */
class Url {

    public static function combine($url1, $url2 = '') {
        if (is_array($url1))
            $url1 = call_user_func_array(array(__CLASS__, __METHOD__), $url1);

        if (is_array($url2))
            $url2 = call_user_func_array(array(__CLASS__, __METHOD__), $url2);

        if (empty($url1))
            return $url2;

        if (empty($url2))
            return $url1;

        if (self::isAbsolute($url2))
            return $url2;

        switch (substr($url1, -1)) {
            case '/':
            case '\\':
            case ':':
                $path = $url1 . $url2;
                break;
            default:
                $path = $url1 . DIRECTORY_SEPARATOR . $url2;
        }

        $args = func_get_args();
        if (count($args) > 2) {
            array_shift($args);
            // TODO: sprawdzić, czy nie potrzeba zamiast 0 dać key($args)
            $args[0] = $path;
            $path = call_user_func_array(array(__CLASS__, __METHOD__), $args);
        }

        return $path;
    }

    public static function isAbsolute($url) {
        return (bool) preg_match("/^(?:(?:https?|ftp):\/\/)/i", $url);
    }

}
