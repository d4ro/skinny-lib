<?php

namespace Skinny\Data;

class Table extends \Skinny\ObjectModelBase {

    /**
     * Konfiguracja modułu
     * @var Store
     * 
     * Dostpne opcje konfiguracji:
     * - templatesPath - ścieżka do katalogu szablonów
     */
    protected static $_config;

    /**
     * Przechowuje typ aktualnego pola (np. "input/text").
     * Typ określa ścieżkę kontrolki z katalogu templatesPath/control/...
     * @var string
     */
    protected $_type = null;

    /**
     * Przechowuje typ kontrolki atrybutów dla aktualnego pola
     * @var string
     */
    protected $_attributesType = null;

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
     * Ścieżka kontrolki atrybutów
     * @var string
     */
    protected $_attributesControlPath = null;

    /**
     * Przechowuje ustawione klasy
     * @var array
     */
    protected $_classes = [];

    /**
     * Label dla pola.
     * @var string
     */
    protected $_label = null;

    /**
     * Dane tabel niezbędne do wyświetlenia :)
     */
    protected $_data;

    public function __construct() {
        $this
                ->type(self::$_config->default->table->control) // domyślna kontrolka formularza
                ->attributesType(self::$_config->default->table->attributesControl) // domyślna kontrolka atrybutów dla formularza
        ;
    }

    /**
     * Dodatkowo ustawia domyślne wartości dla tworzonego pola na podstawie configa.
     * 
     * @param string $name
     * @return static
     */
    public function &__get($name) {
        $new = !isset($this->_items[$name]);

        // domyślna konstrukcja
        $item = parent::__get($name);

        // jeżeli obiekt jest na nowo tworzony należy przypisać mu standardową konfigurację
        if ($new) {
            $item
                    ->type(self::$_config->default->field->control) // domyślna kontrolka dla pól formularza
                    ->attributesType(self::$_config->default->field->attributesControl) // domyślna kontrolka atrybutów dla pól formularza
            ;
        }

        return $item;
    }

    /**
     * Ustawia config.
     * @param \Skinny\Store $config
     */
    public static function setConfig(\Skinny\Store $config) {
        self::$_config = $config;
    }

    /**
     * Ustawia typ kontrolki dla danego pola lub zwraca ustawioną już 
     * wartość jeżeli nie podano argumentu ($type = null).
     * 
     * @param string $type - typ kontrolki znajdującej się w odpowiednim katalogu
     *                       np. "input/text"
     * 
     * @return string|static
     * @throws Exception
     */
    public function type($type = null) {
        if ($type === null) {
            if ($this->_type === null) {
                throw new Exception("Type has not been set");
            }

            if (empty($this->_type)) {
                // Zwraca pusty string jeżeli wartośćnie została ustawiona
                return '';
            } else {
                // Zwraca ustawioną wartość
                return $this->_type;
            }
        } else {
            if (!$this->isRoot()) {
                // Mamy do czynienia z polem
                $path = \Skinny\Path::combine(self::$_config->templatesPath, 'control', $type . '.tpl');
                $controlPath = realpath($path);
            } else {
                // Mamy do czynienia z obiektem głównym - więc szablonem
                $path = \Skinny\Path::combine(self::$_config->templatesPath, 'template', $type . '.tpl');
                $controlPath = realpath($path);
            }

            if (!file_exists($controlPath)) {
                throw new Exception("Control \"$path\" does not exist");
            }

            $this->_type = $type;
            $this->_controlPath = $controlPath;

            return $this;
        }
    }

    /**
     * Ustawia typ kontrolki atrybutów dla danego pola lub zwraca ustawioną już 
     * wartość jeżeli nie podano argumentu ($type = null).
     * 
     * @param string $type - typ kontrolki atrybutów znajdującej się w odpowiednim katalogu (attribute)
     *                       np. "standard"
     * 
     * @return string|static
     * @throws Exception
     */
    public function attributesType($type = null) {
        if ($type === null) {
            if ($this->_attributesType === null) {
                throw new Exception("Attributes type has not been set");
            }

            if (empty($this->_attributesType)) {
                // Zwraca pusty string jeżeli wartość nie została ustawiona
                return '';
            } else {
                // Zwraca ustawioną wartość
                return $this->_attributesType;
            }
        } else {
            $path = \Skinny\Path::combine(self::$_config->templatesPath, 'attribute', $type . '.tpl');
            $controlPath = realpath($path);

            if (!file_exists($controlPath)) {
                throw new Exception("Attributes control \"$path\" does not exist");
            }

            $this->_attributesType = $type;
            $this->_attributesControlPath = $controlPath;

            return $this;
        }
    }

    /**
     * Zwraca obiekt konfiguracyjny.
     * @return \Skinny\Store
     */
    public function getConfig() {
        return self::$_config;
    }

    /**
     * Zwraca ścieżkę aktualnej kontrolki.
     * 
     * @return string
     * @throws Exception
     */
    public function getControlPath() {
        if ($this->_controlPath === null) {
            throw new Exception("No control is set. Yo have to setup 'type' first");
        }

        return $this->_controlPath;
    }

    /**
     * Zwraca ścieżkę aktualnej kontrolki atrybutów.
     * 
     * @return string
     * @throws Exception
     */
    public function getAttributesControlPath() {
        if ($this->_attributesControlPath === null) {
            throw new Exception("No control is set. Yo have to setup 'attributesType' first");
        }

        return $this->_attributesControlPath;
    }

    /**
     * Ustawia wartość dla wybranego atrybutu (nadpisuje poprzednią).
     * 
     * @param string $key klucz atrybutu
     * @param mixed $value
     * @return static
     */
    public function setAttribute($key, $value) {
        $this->_attributes[$key] = $value;
        return $this;
    }

    /**
     * Ustawia wiele atrybutów nadpisując ustawione wartości.
     * 
     * @param array $attributes
     * @return static
     */
    public function setAttributes(array $attributes) {
        $this->_attributes = array_merge($this->_attributes, $attributes);
        return $this;
    }

    /**
     * Pobiera wartość wybranego atrybutu.
     * 
     * @param string $key klucz atrybutu
     * @return mixed
     */
    public function getAttribute($key) {
        return @$this->_attributes[$key];
    }

    /**
     * Zwraca tablicę ustawionych atrybutów.
     * 
     * @return array
     */
    public function getAttributes() {
        return $this->_attributes;
    }

    /**
     * Magiczny call po to aby móc używać m.in. metody o nazwie "class".
     * 
     * @return mixed Metoda w zależności od sytuacji może zwracać inną wartość
     */
    public function __call($name, $arguments) {
        if ($name === 'class') {
            return call_user_method_array('__cls', $this, $arguments);
        } else {
            throw new Exception("No method \"$name\"");
        }
    }

    /**
     * Pobiera atrybut klasy dla danego elementu lub ustawia jego wartość
     * zastępując istniejące klasy.
     * 
     * @param string $class
     * @return string|static
     */
    private function __cls($class = null) {
        if ($class === null) {
            if (!($cls = $this->getAttribute('class'))) {
                $cls = '';
            }
            return $cls;
        } else {
            if (!is_string($class)) {
                throw new Exception('Invalid class name');
            }

            $this->_classes = explode(' ', $class);
            $this->setAttribute('class', implode(' ', $this->_classes));
        }

        return $this;
    }

    /**
     * Pobiera lub ustawia wybrany atrybut w zależności od tego czy $value jest nullem.
     * 
     * @param string $attribute
     * @param mixed $value
     * @return static|string
     */
    private function __getOrSetAttribute($attribute, $value = null) {
        if ($value === null) {
            // pobranie ustawionego atrybutu
            return $this->getAttribute($attribute);
        } else {
            // ustawienie atrybutu
            return $this->setAttribute($attribute, $value);
        }
    }

    /**
     * Dodaje klasę/klasy do istniejących.
     * 
     * @param string $class
     * @return static
     */
    public function addClass($class) {
        if (empty($class) || !is_string($class)) {
            throw new Exception('Invalid class name');
        }

        $this->_classes = array_merge($this->_classes, explode(' ', $class));
        $this->setAttribute('class', implode(' ', $this->_classes));

        return $this;
    }

    /**
     * Usuwa wybraną klasę/klasy.
     * 
     * @param string $class
     */
    public function removeClass($class) {
        if (empty($class) || !is_string($class)) {
            throw new Exception('Invalid class name');
        }

        $classes = explode(' ', $class);

        foreach ($classes as $class) {
            if (($key = array_search($class, $this->_classes)) !== false) {
                unset($this->_classes[$key]);
            }
        }

        $this->setAttribute('class', implode(' ', $this->_classes));

        return $this;
    }

    /**
     * Ustawia atrybut "id".
     * 
     * @param string $id
     * @return static
     */
    public function id($id) {
        $this->setAttribute('id', $id);
        return $this;
    }

    /**
     * Ustawia atrybut "title".
     * 
     * @param string $title
     * @return \Skinny\Data\HtmlBase
     */
    public function title($title) {
        $this->setAttribute('title', $title);
        return $this;
    }

    /**
     * Pobiera lub ustawia label.
     * @param string $label
     * @return \Skinny\Data\Table
     */
    public function label($label = null) {
        if ($label === null) {
            // pobranie wartości
            return $this->_label;
        } else {
            if (!is_string($label)) {
                throw new Exception("Invalid label type. String expected.");
            }

            // ustawienie wartości
            $this->_label = $label;
        }

        return $this;
    }

    /**
     * Ustawia dane niezbędne do wyrenderowania tabeli.
     * 
     * @param mixed $data
     */
    public function setData($data) {
        $this->_data = $data;
    }

    /**
     * Zwraca obiekt danych.
     * 
     * @return mixed
     */
    public function getData() {
        return $this->_data;
    }

}
