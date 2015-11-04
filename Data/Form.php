<?php

namespace Skinny\Data;

use Skinny\DataObject\Store;

/**
 * Wywołanie nieistniejącej metody:
 * - jeżeli jest to metoda o nazwie "class" - nastapi ustawienie/pobranie
 *   atrybutu "class" przy pomocy metody __cls
 */
class Form extends Validate implements \JsonSerializable {

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
     * Przechowuje typ kontrolki atrybutów dla aktualnego pola - domyślnie "standard"
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
     * Przechowuje dane ustawione za pomocą magicznych wywołań.
     * @var array
     */
    protected $_customData = [];

    public function __construct() {
        $this
                ->type(self::$_config->default->form->control) // domyślna kontrolka formularza
                ->method(self::$_config->default->form->method) // domyślna metoda - atrybut
                ->action(self::$_config->default->form->action) // domyślna akcja  - atrybut
                ->attributesType(self::$_config->default->form->attributesControl) // domyślna kontrolka atrybutów dla formularza
        ;
    }

    /**
     * Dodatkowo ustawia domyślne wartości dla tworzonego pola na podstawie configa.
     * 
     * @param string $name
     * @return Form
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
     * Zwraca aktualnie ustawione dane dla całego obiektu.
     * 
     * @return array
     */
    public function getData() {
        $data = parent::getData();
        $data = $this->_filterDataRecursive($data);
        return $data;
    }

    /**
     * Usuwa z data indykatory KeyNotExists.
     * 
     * @param array $data
     * @return array
     */
    private function _filterDataRecursive($data) {
        $result = [];
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $result[$key] = $this->_filterDataRecursive($value);
            } elseif (!($value instanceof KeyNotExist)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Ustawienie konfiguracji modułu
     * @param Store $config
     */
    public static function setConfig(Store $config) {
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
     * Ustawia typ kontrolki atrybutów dla danego pola formularza lub zwraca ustawioną już 
     * wartość jeżeli nie podano argumentu ($type = null)
     * 
     * @param string $type - typ kontrolki atrybutów znajdującej się w odpowiednim katalogu (attribute)
     *                       np. "standard" (domyślnie)
     * 
     * @return string|\Skinny\Data\Form
     * @throws Form\Exception
     */
    public function attributesType($type = null) {
        if ($type === null) {
            if ($this->_attributesType === null) {
                throw new Form\Exception("Attributes type has not been set");
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
     * Zwraca obiekt konfiguracyjny
     * @return Store
     */
    public function getConfig() {
        return self::$_config;
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
     * Zwraca ścieżkę aktualnej kontrolki atrybutów
     * 
     * @return string
     * @throws Form\Exception
     */
    public function getAttributesControlPath() {
        if ($this->_attributesControlPath === null) {
            throw new Form\Exception("No control is set. Yo have to setup 'attributesType' first");
        }

        return $this->_attributesControlPath;
    }

    /**
     * Ustawia wartość dla wybranego atrybutu (nadpisuje poprzednią)
     * 
     * @param string $name nazwa atrybutu
     * @param mixed $value
     * @return \Skinny\Data\Form
     */
    public function setAttribute($name, $value) {
        $this->_attributes[$name] = $value;
        return $this;
    }

    /**
     * Ustawia wiele atrybutów nadpisując ustawione wartości.
     * 
     * @param array $attributes
     * @return \Skinny\Data\Form
     */
    public function setAttributes(array $attributes) {
        $this->_attributes = array_merge($this->_attributes, $attributes);
        return $this;
    }

    /**
     * Pobiera wartość wybranego atrybutu
     * 
     * @param string $name nazwa atrybutu
     * @return mixed
     */
    public function getAttribute($name) {
        return @$this->_attributes[$name];
    }

    /**
     * Sprawdza czy jest ustawiony atrybut o podanej nazwie.
     * 
     * @param string $name
     * @return boolean
     */
    public function hasAttribute($name) {
        return isset($this->_attributes[$name]);
    }

    /**
     * Zwraca tablicę ustawionych atrybutów
     * 
     * @return array
     */
    public function getAttributes() {
        return $this->_attributes;
    }

    /**
     * Magiczny call po to aby móc używać m.in. metody o nazwie "class".
     * 
     * Każda inna nieistniejąca metoda wykona pobranie lub ustawienie 
     * customowej zmiennej (np. icon).
     * 
     * @return mixed Metoda w zależności od sytuacji może zwracać inną wartość
     */
    public function __call($name, $arguments) {
        if ($name === 'class') {
            return call_user_func_array([$this, '__cls'], $arguments);
        } else {
            if (empty($arguments)) {
                // get
                return $this->_getData($name);
            } else {
                // set
                return $this->_setData($name, $arguments[0]);
            }
        }
    }

    /**
     * Ustawia dodatkową daną obiektu w kontenerze _customData.
     * 
     * @param string $name Nazwa zmiennej do ustawienia
     * @param mixed $value Wartość do ustawienia
     * @return \Skinny\Data\Form
     */
    protected function _setData($name, $value) {
        $this->_customData[$name] = $value;
        return $this;
    }

    /**
     * Pobiera ustawioną wartość lub zwraca null jeśli nie ustawiona.
     * 
     * @param string $name Nazwa zmiennej do pobrania
     * @return mixed
     */
    protected function _getData($name) {
        return @$this->_customData[$name];
    }

    /**
     * Pobiera atrybut klasy dla danego elementu lub ustawia jego wartość
     * zastępując istniejące klasy
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
            if (!is_string($class)) {
                throw new Form\Exception('Invalid class name');
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
     * @return \Skinny\Data\Form|string
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
     * Alias do ustawienia atrybutu o nazwie metody.
     * 
     * @param mixed $value
     * @return \Skinny\Data\Form|string
     */
    public function method($value = null) {
        return $this->__getOrSetAttribute('method', $value);
    }

    /**
     * Alias do ustawienia atrybutu o nazwie metody.
     * 
     * @param mixed $value
     * @return \Skinny\Data\Form|string
     */
    public function action($value = null) {
        return $this->__getOrSetAttribute('action', $value);
    }

    /**
     * Alias do ustawienia atrybutu o nazwie metody.
     * 
     * @param mixed $value
     * @return \Skinny\Data\Form|string
     */
    public function placeholder($value = null) {
        return $this->__getOrSetAttribute('placeholder', $value);
    }

    /**
     * Dodaje klasę/klasy do istniejących
     * 
     * @param string $class
     * @return \Skinny\Data\Form
     */
    public function addClass($class) {
        if (empty($class) || !is_string($class)) {
            throw new Form\Exception('Invalid class name');
        }

        $this->_classes = array_merge($this->_classes, explode(' ', $class));
        $this->setAttribute('class', implode(' ', $this->_classes));

        return $this;
    }

    /**
     * Usuwa wybraną klasę/klasy
     * 
     * @param string $class
     */
    public function removeClass($class) {
        if (empty($class) || !is_string($class)) {
            throw new Form\Exception('Invalid class name');
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
     * Sprawdza czy jest ustawiona klasa o podanej nazwie.
     * 
     * @param string $class
     * @return boolean
     * @throws Form\Exception
     */
    public function hasClass($class) {
        if (empty($class) || !is_string($class)) {
            throw new Form\Exception('Invalid class name');
        }

        return in_array($class, $this->_classes);
    }

    /**
     * Ustawia lub pobiera atrybut "id" formularza.
     * 
     * @param string $id
     * @return \Skinny\Data\Form
     */
    public function id($id = null) {
        return $this->__getOrSetAttribute('id', $id);
    }

    /**
     * Alias metody add
     */
    public function addValidator($validator, $errorMsg = null, $options = null) {
        return $this->add($validator, $errorMsg, $options);
    }

    /**
     * Ustawienie serializacji obiektu do JSON.
     * Wynikiem jest tablica indeksowana nazwami pól.
     * Jeżeli pole zawiera jakieś podelementy będzie zawierało klucz "items" - dalej rekurencyjnie.
     * Jeżeli pole jest liściem w polu "value" przyjmnie bieżąco ustawioną wartość.
     * Jeżeli pole zawiera błędy - np. po walidacji - w polu "errors" będzie zawierało tablicę błędów.
     * 
     * @return array
     */
    public function jsonSerialize() {
        $json = [];

        foreach ($this->_items as $name => $item) {
            $json[$name] = [];

            if (!empty($item->_items)) {
                // Przypisanie ewentualnych podelementów jeśli istnieją (rekurencyjnie)
                $json[$name]['items'] = $item;
            } else {
                // Przypisanie ustawionej wartości dla bieżącego pola jeżeli jest liściem
                $json[$name]['value'] = $item->value();
                $json[$name]['keysFromRoot'] = $item->_keysFromRoot;
            }

            // Przypisanie błędów po walidacji pola - jeśli istnieją
            $errors = $item->getErrors();
            if (!empty($errors)) {
                $json[$name]['errors'] = $errors;
            }
        }

        return $json;
    }
    
    /**
     * Zwraca nazwę elementu na podstawie jego zagłębienia - nazwa jak przy formularzach:
     * np. test[pole1][podpole2]
     * 
     * @return string
     */
    public function getArrayLikeName() {
        $name = "";
        foreach($this->_keysFromRoot as $i => $key) {
            if($i == 0) {
                $name = $key;
            } else {
                $name .= "[$key]";
            }
        }
        return $name;
    }

}
