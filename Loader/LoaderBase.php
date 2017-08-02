<?php

namespace Skinny\Loader;

use Skinny\DataObject\Store;

require_once 'Skinny/Loader/LoaderInterface.php';

/**
 * Description of LoaderBase
 *
 * @author Daro
 */
abstract class LoaderBase implements LoaderInterface {

    /**
     * Konfiguracja
     * @var Store
     */
    protected $_config;
    protected $_registered;
    protected $_paths;

    public function __construct($paths, $config = array()) {
        $this->_config     = ($config instanceof Store) ? $config : new Store($config);
        $this->_registered = false;
        $this->_paths      = $paths;
    }

    public function isRegistered() {
        return $this->_registered;
    }

    public function register() {
        if (!$this->_registered) {
            spl_autoload_register(array($this, 'load'));
        }

        $this->_registered = true;
    }

    public abstract function load($className);
}
