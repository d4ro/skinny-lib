<?php

namespace Skinny\View\Html;

class View extends \Skinny\View {

    /**
     * @var Config
     */
    private $__config = null;

    /**
     * @var \Skinny\View\Files
     */
    public $js;

    /**
     * @var \Skinny\View\Files
     */
    public $css;

    public function __construct($config) {
        parent::__construct($config);

        $this->js = new \Skinny\View\Files($this->__config->baseUrl, $this->__config->jsPath, '.js');
        $this->css = new \Skinny\View\Files($this->__config->baseUrl, $this->__config->cssPath, '.css');
    }
    
    protected function _createConfig() {
        return new Config();
    }

}
