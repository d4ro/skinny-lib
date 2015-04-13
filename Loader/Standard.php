<?php

namespace Skinny\Loader;

require_once 'Skinny/Loader/LoaderBase.php';

/**
 * Description of Standard
 *
 * @author Daro
 */
class Standard extends LoaderBase {

    public function register() {
        // ustaw ścieżki include
        set_include_path(
                implode(PATH_SEPARATOR, array(
                    implode(PATH_SEPARATOR, $this->_config->paths->toArray()),
                    implode(PATH_SEPARATOR, $this->_paths->toArray()),
                    get_include_path()
                ))
        );

        // zarejestruj standardowy loader
        spl_autoload_register();
    }

    public function load($className) {
        // NOT USED
    }

}
