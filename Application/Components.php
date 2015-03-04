<?php

namespace Skinny\Application;

use Skinny\Store;

/**
 * Kontener komponentów aplikacji Skinny
 *
 * @author Daro
 */
class Components implements \ArrayAccess {
    
    /**
     * Przechowuje instancję komponentów
     * @var Components
     */
    protected static $instance = null;

    /**
     * Konfiguracja kontenera
     * @var Store
     */
    protected $_config;

    /**
     * Tablica obiektów zainicjalizowanych komponentów
     * @var array
     */
    protected $_components;

    /**
     * Tablica (funkcji) inicjalizatorów komponentów
     * @var array
     */
    protected $_initializers;

    /**
     * Konstruktor kontenera komponentów
     * @param Store $config
     */
    public function __construct($config) {
        $this->_components = array();
        $this->_initializers = array();
        $this->_config = $config;
        self::$instance = $this;
    }
    
    /**
     * Zwraca instancję komponentów
     * @return Components
     */
    public static function getInstance() {
        return self::$instance;
    }

    /**
     * Magiczna funkcja pobierająca komponent o danej nazwie.
     * @param string $name
     * @return object
     */
    public function __get($name) {
        return $this->getComponent($name);
    }

    /**
     * Pobiera z konfiguracji kontenera ustawienie o podanym kluczu. Jeżeli klucz zostanie pominięty, zwracana jest pełna konfiguracja.
     * @param string $key
     * @return mixed
     */
    public function getConfig($key = null) {
        if (null === $key) {
            return $this->_config;
        }

        return $this->_config->$key(null);
    }

    /**
     * Stwierdza, czy komponent o danej nazwie istnieje w aplikacji.
     * @param string $name
     * @return boolean
     */
    public function hasComponent($name) {
        return isset($this->_components[$name]) || $this->hasInitializer($name);
    }

    /**
     * Pobiera obiekt komponentu po jego nazwie. Jeżeli nie został jeszcze zainicjalizowany, robi to w tym momencie.
     * @param string $name
     * @return object
     */
    public function getComponent($name) {
        if (!$this->isInitialized($name)) {
            if (!$this->hasInitializer($name)) {
                return null;
            }

            $this->initialize($name);
        }

        return $this->_components[$name];
    }

    /**
     * Usuwa komponent i inicjalizator komponentu o podanej nazwie.
     * @param string $name
     */
    public function removeComponent($name) {
        if (isset($this->_components[$name])) {
            unset($this->_components[$name]);
        }
        if (isset($this->_initializers[$name])) {
            unset($this->_initializers[$name]);
        }
    }

    /**
     * Stwierdza, czy komponent został już finalnie zainicjalizowany.
     * @param string $name
     * @return boolean
     */
    public function isInitialized($name) {
        return isset($this->_components[$name]) && !$this->hasInitializer($name);
    }

    /**
     * [unused]
     * Stwierdza, czy komponenty o nazwach podanych w tablicy $name zostały już finalnie zainicjalizowane.
     * Jeżeli parametr zostanie pominięty, funkcja stwierdza, czy wszystkie komponenty zostały w pełni zainicjalizowane.
     * @param array $name
     * @return boolean
     */
    public function areInitialized($name = null) {
        if (empty($name)) {
            return empty($this->_initializers);
        }

        $names = (array) $name;
        foreach ($names as $component) {
            if (!$this->isInitialized($component)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Ustawia inicjalizatory. Przyjmuje tablicę, której klucze są nazwami komponentów, a wartościami ich inicjalizatory (funkcje).
     * @param array $initializers
     * @throws \InvalidArgumentException
     */
    public function setInitializers(array $initializers) {
        if (empty($initializers)) {
            return;
        }

        foreach ($initializers as $name => $initializer) {
            if (is_numeric($name)) {
                throw new \InvalidArgumentException('Component name "' . $name . '" cannot be numeric.');
            }
            if (!$initializer instanceof \Closure) {
                throw new \InvalidArgumentException('Component name "' . $name . '" initializer is not a function.');
            }
            $this->_initializers[$name] = $initializer;
        }
    }

    /**
     * Stwierdza, czy podany komponent ma niewywołany inicjalizator.
     * @param string $name
     * @return boolean
     */
    public function hasInitializer($name) {
        if (isset($this->_initializers[$name])) {
            return ($this->_initializers[$name] instanceof \Closure);
        }

        if (isset($this->_components[$name])) {
            return false;
        }

        $file = new \Skinny\File($filename = \Skinny\Path::combine($this->_config->paths->components('components', true), $name . '.php'));

        if (!$file->isReadable()) {
            return false;
        }
        $this->_initializers[$name] = include $filename;
        return ($this->_initializers[$name] instanceof \Closure);
    }

    /**
     * Initializuje komponent(y) o podanej nazwie(ach).
     * @param string|array $name
     * @throws \InvalidArgumentException
     * @throws \BadFunctionCallException
     */
    protected function initialize($name = null) {
        if (null === $name) {
            $name = array_keys($this->_initializers);
        }

        $names = (array) $name;
        foreach ($names as $component) {
            if ($this->isInitialized($component)) {
                // TODO: a może nic nie robić?
                throw new \InvalidArgumentException('Component name "' . $component . '" has already been initialized.');
            }

            if (!$this->hasInitializer($component)) {
                throw new \InvalidArgumentException('Component name "' . $component . '" does not have proper initializer.');
            }

            $initializer = $this->_initializers[$component];
            $result = $initializer();
            if (null === $result) {
                throw new \BadFunctionCallException('Component name "' . $component . '" initializer does not return object.');
            }

            $this->_components[$component] = $result;
            unset($this->_initializers[$component]);
        }
    }

    /**
     * Stwierdza, czy komponent o podanej nazwie istnieje.
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset) {
        return $this->hasComponent($offset);
    }

    /**
     * Pobiera komponent o podanej nazwie. W razie potrzeby inicjalizuje go.
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->getComponent($offset);
    }

    /**
     * Ustawia inicjalizator komponentu pod podaną nazwą.
     * @param string $offset
     * @param \Closure $value
     */
    public function offsetSet($offset, $value) {
        $this->setInitializers([$offset => $value]);
    }

    /**
     * Usuwa komponent o podanej nazwie.
     * @param string $offset
     */
    public function offsetUnset($offset) {
        $this->removeComponent($offset);
    }

}
