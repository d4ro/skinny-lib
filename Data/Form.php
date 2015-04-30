<?php

namespace Skinny\Data;

class Form extends Validate {

    /**
     * Konfiguracja modułu
     * @var Store
     * 
     * Dostpne opcje konfiguracji:
     * - templatesPath - ścieżka do szablonów formularzy
     */
    protected static $_config;
    
    /**
     * Przechowuje typ aktualnego pola (np. "input/text")
     * @var string
     */
    protected $_type = null;
    
    /**
     * Label dla bieżącego pola formularza
     * @var string
     */
    protected $_label = null;
    
    /**
     * Przechowuje wartość pola
     * @var string
     */
    protected $_value = null;
    
    /**
     * Tablica atrybutów elementu
     * @var array
     */
    protected $_attributes = [];

    /**
     * Ustawienie konfiguracji modułu
     * @param \Skinny\Store $config
     */
    public static function setConfig(Store $config) {
        self::$_config = $config;
    }
    
    /**
     * Ustawia typ kontrolki dla danego pola formularza
     * 
     * @param string $type - typ kontrolki znajdującej się w odpowiednim katalogu
     *                       np. "input/text"
     */
    public function type($type) {
        $controlPath = \Skinny\Path::combine(self::$_config->templatesPath, 'control', $type . '.tpl');
        if(!file_exists($controlPath)) {
//            throw new exc
        }
    }
    
    /**
     * 
     * @param type $label
     * @throws form\exception
     */
    public function label($label) {
        if(empty($label) || !is_string($label)) {
            throw new form\exception("Invalid label type");
        }
        
        $this->_label = $label;
    }

    /**
     * Alias metody add
     */
    public function addValidator($validator, $errorMsg = null, $options = null) {
        return $this->add($validator, $errorMsg, $options);
    }

}