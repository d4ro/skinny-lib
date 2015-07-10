<?php

namespace Skinny\View;

/**
 * Model obsługujący renderowanie widoku strony.
 * 
 * @property Files $js  Obiekt plików JavaScript - umożliwia zarządzanie wczytywanymi
 *                      skryptami do widoku
 * 
 * @property Files $css Obiekt plików CSS - umożliwia zarządzanie wczytywanymi
 *                      skryptami do widoku
 */
class View extends \Skinny\Store {

    /**
     * Config aplikacji
     * @var \Skinny\Store
     */
    private $__config;

    /**
     * Obiekt plików js
     * @var Files
     */
    public $js;

    /**
     * Obiekt plików css
     * @var Files
     */
    public $css;
    
    /**
     * Obiekt umożliwiający renderowanie widoku
     * @var Renderer
     */
    protected $_renderer = null;
    
    protected $_layoutsDir;
    protected $_layout;

    /**
     * 
     * @param type $config  Konfiguracja widoku - dostępne opcje:
     *                      - baseUrl       - ścieżka aplikacji
     * 
     *                      - jsPath        - ściezka do katalogu z plikami JavaScript
     *                      - jsExtension   - rozszerzenie plików JavaScript
     * 
     *                      - cssPath       - ściezka do katalogu z plikami CSS
     *                      - cssExtension  - rozszerzenie plików CSS
     * 
     *                      - layoutsDir    - ścieżka do katalogu z plikami layoutów
     *                      - layout        - domyślny layout
     */
    public function __construct($config) {
        parent::__construct();
        
        $this->_validateConfig($config);
        
        

        $this->__config = $config;
        
        $this->js = new Files(
                $this->__config->router->baseUrl, $config->paths->js, '.js'
        );
        $this->css = new Files(
                $this->__config->router->baseUrl, $config->paths->css, '.css'
        );

        // ustawienie ścieżki do plików obrazków
        $this->paths->img = $this->__config->paths->img;
        
        // ustawienie ścieżki do plików layoutu
        $this->_layoutsDir = $config->view->layout;
    }
    
    protected function _validateConfig($config) {
        $v = new \Skinny\Data\Validate();
        $v->baseUrl
                ->required()
                ->add(function() {
                    return is_string($this->value);
                });
        $v->a;
    }
    
    /**
     * Ustawia obiekt renderera.
     * 
     * @param \Skinny\View\Renderer $renderer
     * @return \Skinny\View\View
     * @throws Exception
     */
    public function setRenderer($renderer) {
        if(!($renderer instanceof Renderer)) {
            throw new Exception('Renderer has to be an instance of Skinny\\View\\Renderer');
        }
        
        $this->_renderer = $renderer;
        
        return $this;
    }
    
    /**
     * Ustawia nazwę layoutu.
     * 
     * @param string $layout
     * @return \Skinny\View\View
     */
    public function setLayout($layout) {
//        if()
        $this->_layout = $layout;
        return $this;
    }

}
