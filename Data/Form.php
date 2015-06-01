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
     * Ścieżka kontrolki
     * @var string
     */
    protected $_controlPath = null;

    /**
     * Ustawienie konfiguracji modułu
     * @param \Skinny\Store $config
     */
    public static function setConfig(\Skinny\Store $config) {
        self::$_config = $config;
    }

    /**
     * Ustawia typ kontrolki dla danego pola formularza lub zwraca ustawioną już 
     * wartość jeżeli nie podano argumentu ($type = null)
     * 
     * @param string $type - typ kontrolki znajdującej się w odpowiednim katalogu
     *                       np. "input/text"
     * 
     * @return string|\Skinny\Data\Form
     * @throws Form\Exception
     */
    public function type($type = null) {
        if ($type === null) {
            if ($this->_type === null) {
                throw new Form\Exception("Type has not been set");
            }

            if (empty($this->_type)) {
                // Zwraca pusty string jeżeli wartośćnie została ustawiona
                return '';
            } else {
                // Zwraca ustawioną wartość
                return $this->_type;
            }
        } else {
            if ($this->_name) {
                // Jeżeli ma nazwę to znaczy że mamy do czynienia z polem formularza
                $controlPath = realpath(\Skinny\Path::combine(self::$_config->templatesPath, 'control', $type . '.tpl'));
            } else {
                // W przypadku braku nazwy mamy do czynienia z obiektem formularza - więc szablonem
                $controlPath = realpath(\Skinny\Path::combine(self::$_config->templatesPath, $type . '.tpl'));
            }

            if (!file_exists($controlPath)) {
                throw new Form\Exception("Control $controlPath does not exist");
            }

            $this->_type = $type;
            $this->_controlPath = $controlPath;

            return $this;
        }
    }

    /**
     * Zwraca ścieżkę aktualnej kontrolki
     * 
     * @return string
     * @throws Form\Exception
     */
    public function getControlPath() {
        if ($this->_controlPath === null) {
            throw new Form\Exception("No control is set. Yo have to setup 'type' first");
        }

        return $this->_controlPath;
    }

    /**
     * Ustawia wartość dla wybranego atrybutu (nadpisuje poprzednią)
     * 
     * @param string $key klucz atrybutu
     * @param mixed $value
     * @return \Skinny\Data\Form
     */
    public function setAttribute($key, $value) {
        $this->_attributes[$key] = $value;
        return $this;
    }
    
    /**
     * Ustawia wiele atrybutów nadpisując ustawione wartości
     * 
     * @param array $attributes
     * @return \Skinny\Data\Form
     */
    public function setAttributes(array $attributes) {
        $this->_attributes = array_merge($this->_attributes, $attributes);
        return $this;
    }
    
//    public function setAttributes

    /**
     * Pobiera wartość wybranego atrybutu
     * 
     * @param string $key klucz atrybutu
     * @return mixed
     */
    public function getAttribute($key) {
        return @$this->_attributes[$key];
    }

    /**
     * Magiczny call po to aby móc używać metody o nazwie "class"
     */
    public function __call($name, $arguments) {
        if ($name === 'class') {
            return call_user_method_array('__cls', $this, $arguments);
        } else {
            throw new Form\Exception("No method \"$name\"");
        }
    }

    /**
     * Pobiera atrybut klasy dla danego elementu lub ustawia jego wartość
     * 
     * @param string $class
     * @return string|\Skinny\Data\Form
     */
    private function __cls($class = null) {
        if ($class === null) {
            if (!($cls = $this->getAttribute('class'))) {
                $cls = '';
            }
            return $cls;
        } else {
            $this->setAttribute('class', $class);
        }

        return $this;
    }

    /**
     * Alias metody add
     */
    public function addValidator($validator, $errorMsg = null, $options = null) {
        return $this->add($validator, $errorMsg, $options);
    }

//    public function getControlPath() {
//        if($this->name) {
//            // pole formularza
//            
//        } else {
//            // formularz
//            $controlPath = \Skinny\Path::combine(self::$_config->templatesPath, 'control', $type . '.tpl');
//        }
//    }
}
