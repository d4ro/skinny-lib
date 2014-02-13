<?php

namespace Skinny;

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
class Store implements \JsonSerializable {

    /**
     * Zawartość store
     * @var array 
     */
    protected $items;

    // konstruktor
    public function __construct($obj = null) {
        $this->fromObj($obj);
    }

    // odczyt nieistniejącej włąściwości
    public function &__get($name) {
        if (!isset($this->items[$name]))
            $this->items[$name] = new self();
        return $this->items[$name];
    }

    // zapis do nieistniejącej właściwości
    public function __set($name, $value) {
        $this->items[$name] = $value;
    }

    // isset lub empty na nieistniejącej właściwości
    public function __isset($name) {
        return isset($this->items[$name]) &&
                (!($this->items[$name] instanceof self) || ($this->items[$name] instanceof self) && !$this->items[$name]->isEmpty());
    }

    // unsetowanie nieistniejącej właściwości
    public function __unset($name) {
        unset($this->items[$name]);
    }

    public function __toString() {
        return json_encode($this);
    }

    public function isEmpty() {
        return $this->length() == 0;
    }

    public function length() {
        $items = count($this->items);
        foreach ($this->items as $item) {
            if ($item instanceof self && $item->isEmpty())
                $items--;
        }

        return $items;
    }

    public function jsonSerialize() {
        return $this->items;
    }

    public function toArray() {
        $array = array();
        foreach ($this->items as $key => $value) {
            if ($value instanceof self) {
                if (!$value->isEmpty())
                    $array[$key] = $value->toArray();
            }
            else
                $array[$key] = $value;
        }
        return $array;
    }

    public function __clone() {
        foreach ($this->items as $key => $value) {
            if ($value instanceof self)
                if (!$value->isEmpty())
                    $this->items[$key] = clone $value;
                else
                    unset($this->items[$key]);
        }
    }

    public function cleanup() {
        foreach ($this->items as $key => $value) {
            if ($value instanceof self && $value->isEmpty())
                unset($this->items[$key]);
        }
    }

    public function fromObj($obj) {
        $this->items = array();
        $this->merge($obj);
    }

    public function __call($name, $arguments) {
        if (isset($this->$name))
            return $this->items[$name];

        $default = isset($arguments[0]) ? $arguments[0] : null;
        $create = isset($arguments[1]) ? $arguments[1] : false;
        $return_default = isset($arguments[2]) ? $arguments[2] : isset($arguments[0]);

        if ($create) {
            if ($return_default)
                $this->$name = $default;
            return $this->$name;
        }

        if ($return_default)
            return $default;

        return new self;
    }

    public function merge($obj) {
        if ($obj instanceof self)
            $obj = $obj->items;

        $obj = (array) $obj;
        foreach ($obj as $key => $value) {
            if ($value instanceof self || is_array($value)) {
                if (isset($this->items[$key]) && (is_array($this->items[$key]) || $this->items[$key] instanceof self)) {
                    if (is_array($this->items[$key]))
                        $this->items[$key] = new self($this->items[$key]);
                    $this->items[$key]->merge($value);
                }
                else
                    $this->items[$key] = new self($value);
            } elseif (null !== $value) {
                $this->items[$key] = $value;
            }
        }
    }

}