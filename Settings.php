<?php

namespace Skinny;

/**
 * Description of Settings
 *
 * @author Daro
 */
class Settings {

    /**
     *
     * @var Store 
     */
    protected $_config;
    protected $_filename;

    public function __construct($config_path = 'config') {
        $this->_filename = $config_path . '/settings.conf.php';
        if (file_exists($this->_filename)) {
            $this->_config = new Store(include $this->_filename);
        } else {
            $this->_config = new Store ();
        }
    }

    public function getSettings() {
        return $this->_config;
    }

    public function __get($name) {
        return $this->getSetting($name);
    }

    public function getSetting($name) {
        return $this->_config->$name(null);
    }

    public function __set($name, $value) {
        $this->setSetting($name, $value);
    }

    public function setSetting($name, $value) {
        $parts = explode('.', $name);

        $step = &$this->_config;

        for ($i = 0, $count = count($parts); $i < $count; $i++) {
            $step = &$step->{$parts[$i]};
        }

        $step = $value;
    }

    public function unsetSetting($name) {
        $this->setSetting($name, null);
    }

    public function save() {
        $string = '<?php\n\n// This file is automaticaly created by dynamic settings utility in Skinny skeleton application.\n\nreturn ' . var_export($this->_config->toArray(), true) . ';';
        file_put_contents($this->_filename, $string);
    }

}
