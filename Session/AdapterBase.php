<?php

namespace Skinny\Session;

/**
 * Description of AdapterBase
 *
 * @author Daro
 */
abstract class AdapterBase implements AdapterInterface {

    protected $_config;

    public function setSessionConfig($config) {
        $this->_config = $config;
    }

}
