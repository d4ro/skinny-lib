<?php

namespace Skinny;

/**
 * Model obsługujący renderowanie widoku strony.
 */
class View extends \Skinny\Store {

    /**
     * Obiekt konfiguracyjny.
     * @var View\Config
     */
    protected $_config = null;
    
    /**
     * @var \Skinny\View\Files
     */
    public $js;

    /**
     * @var \Skinny\View\Files
     */
    public $css;
    
    public function __construct($config) {
        die('ddd');
        parent::__construct();

        $this->_config = $this->setConfig($config);
        
        var_dump($config);
        die('ddd');
        
        $this->js = new View\Files($this->_config->baseUrl, $this->_config->jsPath, $this->_config->jsExtension);
        $this->css = new View\Files($this->_config->baseUrl, $this->_config->cssPath, $this->_config->cssExtension);
    }
    
    /**
     * 
     * @param type $config
     * @return \Skinny\View
     */
    public function setConfig($config) {
        if($this->_config === null) {
            $this->_config = new View\Config();
        }
        
        $this->_config->merge($config);
        
        return $this;
    }
    
    /**
     * Zwraca obiekt konfiguracyjny View umożliwiający edycję bieżących ustawień.
     * @return View\Config
     */
    public function getConfig() {
        return $this->_config;
    }

}