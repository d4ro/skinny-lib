<?php

namespace Skinny\DataObject;

require_once __DIR__ . '/ObjectModelBase.php';

/**
 * Klasa mająca na celu ułatwić pracę z tablicami.
 * Zamiast $var = array(); : $var = new Store();
 * Zamiast $var['a'] = 'b'; : $var->a = 'b';
 * Zamiast if(!isset($var['b']) $var['b'] = array(); $var['b']['c'] = 'd'; : $var->b->c = 'd';
 * Zamiast if(isset($var['c']) && isset($var['c']['d']) return $var['c']['d']; : if(isset($var->c->d)) return $var->c->d;
 * 
 * Ustawianie pola:
 * $var->a = 'b';
 * 
 * Ustawianie dowolnie zagłębionego pola:
 * $var->a->b->c->d->e = 'f';
 * Polecenie utworzy pełną ścieżkę, gdy ta nie istnieje.
 * 
 * Pobranie pola do sprawdzenia typu i wartości:
 * $a = $var->b;
 * 
 * Pobranie dowolnie zagłębionego pola do sprawdzenia typu i wartości:
 * $a = $var->b->c->d->e->f;
 * Polecenie utworzy pełną ścieżkę, gdy ta nie istnieje. Jednakże `f` pozostanie pustym Store.
 * 
 * Powyższy przykład bez ryzyka utworzenia pustych Store:
 * $a = $var->b()->c()->d()->e()->f();
 * 
 * Pobranie pola do prostego sprawdzenia wartości (wartość argumentu, gdy nie istnieje lub jest pustym Store):
 * $a = $var->b(null);
 *
 * Pobranie dowolnie zagłębionego pola do prostego sprawdzenia wartości (wartość argumentu, gdy nie istnieje lub jest pustym Store):
 * $a = $var->b->c->d->e->f(false);
 * Polecenie utworzy pełną ścieżkę, gdy ta nie istnieje. Jednakże `e` pozostanie pustym Store, a `f` nie zostanie utworzone.
 * 
 * Powyższy przykład bez ryzyka utworzenia pustych Store:
 * $a = $var->b()->c()->d()->e()->f(false);
 * 
 * Pobranie pola lub wartości domyślnej:
 * $a = $var->b('c');
 * Zwróci wartość `b` lub 'c', gdy `b` nie istnieje lub jest pustym Store
 * 
 * Pobranie pola lub, gdy jest puste, przypisanie wartości domyślnej i jej użycie:
 * $a = $var->b('c', true);
 * Zwróci wartość `b` lub 'c', gdy `b` nie istnieje lub jest pustym Store
 * Gdy `b` nie istnieje lub jest pustym Store ma od teraz wartość 'c'
 * 
 * Równoważności (gdy `b` istnieje i nie jest pustym Store zawsze zostanie zwrócone):
 * $a = $var->b('c', false); <==> $a = $var->b('c');
 * $a = $var->b('c', false, true); <==> $a = $var->b('c');
 * $a = $var->b('c', true, true); <==> $a = $var->b('c', true);
 * $a = $var->b('c', true, false); <==> $a = $var->b;
 * $a = $var->b('c', false, false); <==> $a = $var->b();
 * 
 * Przykłady błędów:
 * 
 * W przypadku braku `c` dane `d` i `e` zostaną zagubione:
 * $var->a->b->c()->d->e = 'f';
 * 
 * W przypadku braku `c` wystąpi błąd traktowania stringu 'g' jako obiektu:
 * $var->a->b->c('g')->d->e = 'f';
 * 
 * @author Daro
 */
class Store extends ObjectModelBase implements \JsonSerializable {

    /**
     * Obiekt Store może zostać zbudowany z innego obiektu lub tablicy.
     * 
     * @param object|array $obj
     */
    public function __construct($obj = null) {
        if ($obj !== null) {
            if (!is_object($obj) && !is_array($obj)) {
                require_once __DIR__ . '/IOException.php';
                throw new IOException('Invalid input data');
            } else {
                $this->merge($obj);
            }
        }
    }

    /**
     * Ustawia właściwość klasy, która ma być serializowana w przypadku wywołania
     * json_encode/decode.
     * 
     * @return array
     */
    public function jsonSerialize() {
        return $this->_items;
    }

    /**
     * Metoda serializująca obiekt do JSON'a.
     * 
     * @return string
     */
    public function __toString() {
        return json_encode($this);
    }

    /**
     * Konwersja obiektu do tablicy.
     * 
     * @return array
     */
    public function toArray() {
        $array = array();
        foreach ($this->_items as $key => $value) {
            if ($value instanceof self) {
                if (!$value->isEmpty()) {
                    $array[$key] = $value->toArray();
                }
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    /**
     * Definicja operacji które mają zostać wykonane przy klonowaniu obiektu.
     * Metoda wywoływana jest automatycznie po tym jak obiekt jest już sklonowany.
     */
    public function __clone() {
        foreach ($this->_items as $key => $value) {
            if ($value instanceof self) {
                if (!$value->isEmpty()) {
                    $this->_items[$key] = clone $value;
                } else {
                    unset($this->_items[$key]);
                }
            }
        }
    }

    /**
     * Przeciąża domyślną metodę tak aby zawsze konstruowane były obiekty self.
     * 
     * @return self
     */
    protected function _createObject() {
        return new self();
    }

    /**
     * Funkcja czyści wszystkie puste wartości będące instancją tej klasy.
     */
    public function cleanup() {
        foreach ($this->_items as $key => $value) {
            if ($value instanceof self && $value->isEmpty()) {
                unset($this->_items[$key]);
            }
        }
    }

    /**
     * Magic call na nie istniejącej właściwości umożliwia zwrócenie wartości
     * domyslnej ustawionej jako pierwszy argument funkcji.
     * 
     * 
     * @param string $name
     * @param array $arguments [{mixed} $defaultIfNotExist, {boolean} $writeToStore=false, {boolean} $returnDefaultIfNotExist]
     * @return self|$defaultIfNotExist
     * 
     * @example Przykłady w dokumentacji klasy
     */
    public function __call($name, $arguments) {
        if (isset($this->$name)) {
            if ($this->$name instanceof \Closure) {
                $closure = $this->$name;
                return call_user_func_array($closure, $arguments);
            } else {
                return $this->_items[$name];
            }
        }

        $default = isset($arguments[0]) ? $arguments[0] : null;
        $create = isset($arguments[1]) ? $arguments[1] : false;
        $return_default = isset($arguments[2]) ? $arguments[2] : isset($arguments[0]);

        if ($create) {
            if ($return_default) {
                $this->$name = $default;
            }
            return $this->$name;
        }

        if ($return_default) {
            return $default;
        }

        return $this->_createObject();
    }

    /**
     * Merge właściwości bieżącego obiektu z tym podanym jako argument metody.
     * Metoda rzutuje podany argument do tablicy po czym dokonuje merge'a.
     * 
     * @param mixed $obj
     * @return self
     */
    public function merge($obj) {
        if ($obj instanceof self) {
            $obj = $obj->_items;
        }

        $obj = (array) $obj;
        foreach ($obj as $key => $value) {
            if ($value instanceof self || is_array($value)) {
                if (isset($this->_items[$key]) && (is_array($this->_items[$key]) || $this->_items[$key] instanceof self)) {
                    if (is_array($this->_items[$key])) {
                        $this->_items[$key] = new self($this->_items[$key]);
                    }
                    $this->_items[$key]->merge($value);
                } else {
                    $this->_items[$key] = new self($value);
                }
            } elseif (null !== $value) {
                $this->_items[$key] = $value;
            }
        }

        return $this;
    }

}
