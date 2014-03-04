<?php

namespace Skinny\Loader;

require_once 'Skinny\Loader\LoaderBase.php';

/**
 * Description of Prefix
 *
 * @author Daro
 */
class Prefix extends LoaderBase {

    public function load($class_name) {
        if(strpos($class_name, 'Zend') !== false) {
            if(isset($this->_config->Zend)) {
                $modules = explode('_', $class_name);
                $path = $this->_config->Zend;
                foreach($modules as $k => $v) {
                    if($k > 0) {
                        $path .= '/' . $v;
                    }
                }
                if(is_readable($path .= '.php')) {
                    include $path;
                }
            }
        }
    }

}