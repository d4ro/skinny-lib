<?php

namespace Skinny\Loader;

require_once 'Skinny\Loader\LoaderBase.php';

/**
 * Description of Prefix
 *
 * @author Daro
 */
class Prefix extends LoaderBase {

    public function load($className) {
        foreach ($this->_config->toArray() as $prefix => $path) {
            if (strpos($className, $prefix) !== 0) {
                continue;
            }

            $parts = explode('_', $className);
            foreach ($parts as $index => $part) {
                if ($index > 0) {
                    $path .= '/' . $part;
                }
            }

            if (is_readable($path .= '.php')) {
                include_once $path;
            }

            break;
        }

        return class_exists($className);
    }

}
