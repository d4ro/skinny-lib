<?php

namespace Skinny;

/**
 * Description of Path
 *
 * @author Daro
 */
class Path {

    /**
     * Łączy podane ścieżki w jedną, dodając znak rozdzielający katalogi, jeśli to konieczne.
     * Jeżeli argument jest ścieżką bezwzględną, poprzedzający nie będzie brany pod uwagę.
     * Metoda zezwala na dowolną ilość argumentów. Kolejne będą uwzględniane rekurencyjnie.
     * @param string|array $path1
     * @param string|array $path2
     * @return string
     */
    public static function combine($path1, $path2 = '') {
        if (is_array($path1))
            $path1 = call_user_func_array(array(__CLASS__, __METHOD__), $path1);

        if (is_array($path2))
            $path2 = call_user_func_array(array(__CLASS__, __METHOD__), $path2);

        if (empty($path1))
            return $path2;

        if (empty($path2))
            return $path1;

        if (self::isAbsolute($path2))
            return $path2;

        switch (substr($path1, -1)) {
            case '/':
            case '\\':
            case ':':
                $path = $path1 . $path2;
                break;
            default:
                $path = $path1 . DIRECTORY_SEPARATOR . $path2;
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

    /**
     * Stwierdza, czy podana ścieżka jest scieżką bezwględną.
     * @param string $path
     * @return boolean
     */
    public static function isAbsolute($path) {
        if (empty($path))
            return false;

        return($path[0] == '/' || $path[0] == '\\');
    }

}
