<?php

namespace Skinny\Loader;

require_once 'Skinny\Loader\LoaderBase.php';

/**
 * Description of NSpace
 *
 * @author Daro
 */
class NSpace extends LoaderBase {

    public function load($className) {
        $className = trim($className, '\\');

//echo getcwd();

        foreach ($this->_config->toArray() as $namespace => $path) {

//            var_dump($path);
//            die();
            if (strpos($className, $namespace) !== 0) {
                continue;
            }

            $parts = explode('\\', $className);
            foreach ($parts as $index => $part) {
                if ($index > 0) {
                    $path .= DIRECTORY_SEPARATOR . $part;
                }
            }
            $path .= '.php';
            
            if (is_readable($path)) {
                include_once $path;
            }

            break;
        }

        return class_exists($className);
    }

}
