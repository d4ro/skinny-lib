<?php

namespace Skinny;

/**
 * Model obsługujący renderowanie widoku strony.
 */
class View extends DataObject\ArrayWrapper {

    /**
     * Obiekt konfiguracyjny.
     * @var View\Config
     */
    protected $_config = null;

    /**
     * Pliki js dołączane na końcu "body".
     * @var \Skinny\View\Files
     */
    public $js;
    
    /**
     * Pliki js dołączane na końcu "head".
     * @var \Skinny\View\Files
     */
    public $jsHead;

    /**
     * Pliki css dołączane w sekcji "head".
     * @var \Skinny\View\Files
     */
    public $css;
    
    /**
     * Kontener zmiennych JavaScript.
     * @var array
     */
    protected $_jsVars = [];

    /**
     * Obiekt renderera ustawionego dla tego widoku.
     * @var \app\module\smartyRenderer
     */
    private $__renderer = null;

    public function __construct($config) {
//        parent::__construct();

        $this->setConfig($config);

        $this->js = new View\Files($this->_config->baseUrl, $this->_config->applicationPath, $this->_config->jsPath, $this->_config->jsExtension);
        $this->jsHead = new View\Files($this->_config->baseUrl, $this->_config->applicationPath, $this->_config->jsPath, $this->_config->jsExtension);
        $this->css = new View\Files($this->_config->baseUrl, $this->_config->applicationPath, $this->_config->cssPath, $this->_config->cssExtension);
    }
    
    /**
     * Ustawienie bieżącego renderera.
     * 
     * @param \app\module\smartyRenderer $renderer
     * @return \Skinny\View
     */
    public function setRenderer(View\Renderer $renderer) {
        $this->__renderer = $renderer;
        return $this;
    }
    
    /**
     * Pobranie bieżącego renderera.
     * 
     * @return View\Renderer
     */
    public function getRenderer() {
        return $this->__renderer;
    }

    /**
     * Ustawienie configa (merge).
     * 
     * @param View\Config|array $config
     * @return \Skinny\View
     */
    public function setConfig($config) {
        if ($this->_config === null) {
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

    /**
     * Widok nie będzie renderowany przez renderer.
     * 
     * @return \Skinny\View
     */
    public function setNoRender() {
        $this->_config->isRenderAllowed = false;
        return $this;
    }

    /**
     * Czy widok ma być renderowany?
     * 
     * @return boolean
     */
    public function isRenderAllowed() {
        return $this->_config->isRenderAllowed;
    }

    /**
     * Ustawia layout dla bieżącego żądania.
     * 
     * @param string $layout
     */
    public function setLayout($layout) {
        $this->_config->layout = $layout;
    }

    /**
     * Sprawdza czy layout został ustawiony.
     * 
     * @return boolean
     */
    public function isLayoutSet() {
        return !empty($this->_config->layout);
    }

    /**
     * Pobiera ustawiony layout.
     * 
     * @return string
     */
    public function getLayout() {
        return $this->_config->layout;
    }

    /**
     * Konfiguruje i zwraca pełną ścieżkę (absolutną lub url) do aktualnie ustawionego
     * pliku layoutu.
     * 
     * @return string
     * @throws View\Exception
     */
    public function getCurrentLayoutPath() {
        $v = new Data\Validate();
        $v->layout
                ->required()
                ->add(new Data\Validator\NotEmpty(), "Layout file has not been set");
        $v->layoutsPath
                ->required()
                ->add(new Data\Validator\NotEmpty(), "Layouts path has not been set");

        // Walidacja konfiguracji
        if (!$v->isValid($this->_config->toArray())) {
            throw new View\Exception(json_encode($v->getAllErrors()));
        }

        if (
                !\Skinny\Path::isAbsolute($this->_config->layout) &&
                !\Skinny\Url::hasProtocol($this->_config->layout)
        ) {

            $file = \Skinny\Path::combine($this->_config->layoutsPath, $this->_config->layout);
        }

        return realpath($file . $this->_config->templatesExtension);
    }
    
    /**
     * Ustawia zmienną JavaScript do przekazania do widoku.
     * Jeżeli $key jest tablicą, wszystkie wartości dla podanych kluczy tej tablicy zostaną ustawione.
     * 
     * @param string|array $key
     * @param mixed $value
     */
    public function setScriptVar($key, $value) {
        if (!isset($this->_jsVars)) {
            $this->_jsVars = [];
        }
        
        if (!empty($key)) {
            if (is_array($key)) {
                $this->_jsVars = array_merge($this->_jsVars, $key);
            } else {
                $this->_jsVars[$key] = $value;
            }
        }
    }
    
    /**
     * Zwraca ustawione zmienne JavaScript zakodowane w JSON.
     * @return string JSON
     */
    public function getScriptVars() {
        return json_encode($this->_jsVars);
    }

    public function display() {
        if(($renderer = $this->getRenderer()) === null) {
            throw new View\Exception("Renderer has not been set");
        }
        if(!is_readable(($currentLayoutPath = $this->getCurrentLayoutPath()))) {
            throw new View\Exception("Layout file is not readable");
        }
                
        echo $renderer->fetch($currentLayoutPath, ['this' => $this]);
    }
    
    public function clear() {
        parent::clear();
        
        $this->js->clear();
        $this->css->clear();
        $this->jsHead->clear();
        $this->_jsVars = [];
    }

}
