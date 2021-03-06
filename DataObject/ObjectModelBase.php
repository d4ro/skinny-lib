<?php

namespace Skinny\DataObject;

/**
 * Jest to model umożliwiający wygodniejszą pracę z tablicami/obiektami.
 * Pozwala m.in. na dynamiczne tworzenie łańcuchów na nieistniejących właściwościach, np:
 * $a->v1->v2->v3 = "value"; - właściwości v1 oraz v2 zostaną utworzone automatycznie.
 * czy też poruszanie się po różnych poziomach właściwości w celu zachowania 
 * łańcuchowości, np:
 * $a->v1->v2->v3->jakasFunkcja()->parent('v2')->innaFunkcja()->root()->funkcja();
 * 
 * @author Daro|Wodzu
 */
abstract class ObjectModelBase implements \IteratorAggregate {

    /**
     * Przechowuje wszystkie stworzone elementy.
     * 
     * @var array
     */
    protected $_items = [];

    /**
     * Przechowuje nazwę bieżącego elementu.
     * 
     * @var string
     */
    protected $_name = null;

    /**
     * Wskazuje na element rodzica lub null jeśli obiekt jest korzeniem.
     * 
     * @var static
     */
    protected $_parent = null;

    /**
     * Wskazuje na element root.
     * 
     * @var static
     */
    protected $_root = null;

    /**
     * Umożliwia iterowanie bezpośrednio po elementach tablicy $_items.
     * 
     * @return ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->_items);
    }

    /**
     * Odczyt nieistniejącej właściwości tworzy nowy obiekt tej lub innej klasy
     * zgodnie z dokumentacją metody _createObject.
     * 
     * @param string $name
     * @return mixed
     */
    public function &__get($name) {
        if ($this->_root === null) {
            $this->_root = $this;
        }
        if (!isset($this->_items[$name])) {
            $this->_items[$name] = $this->_createObject($name);
        }
        return $this->_items[$name];
    }

    /**
     * Tworzy lub zwraca istniejący element - alias to magicznej metody get.
     * Metoda może być wieloargumentowa - wtedy każdy kolejny argumenty to kolejny poziom zagłębienia.
     * 
     * @param string $name
     * @return static
     * 
     * @todo Przepisać w taki sposób aby było obsługiwane nie przez magiczne metody.
     */
    public function child($name) {
        if (func_num_args() > 1) {
            $item = $this;
            $args = func_get_args();
            foreach ($args as $arg) {
                $item = $item->{$arg};
            }
        } else {
            $item = $this->{$name};
        }

        return $item;
    }

    /**
     * Zapis nieistniejącej właściwości. Wartość może być dowolna, czyli istnieje
     * możliwość przerwania łańcucha w taki sposób żeby dany poziom przechowywał
     * jedynie ustawioną wartość.
     * 
     * Aby uniknąć przerwania łańcucha należy odpowiednio przeciążyć tą metodę.
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->_items[$name] = $value;
    }

    /**
     * Sprawdza czy bieżący poziom jest ustawiony. Poziom NIE JEST ustawiony gdy
     * wartość tego poziomu lub wartość wszystkich jego podelementów jest pusta.
     * 
     * @param string $name
     * @return boolean
     */
    public function __isset($name) {
        if (!isset($this->_items[$name])) {
            return false;
        }

        if (!($this->isSelf($this->_items[$name]))) {
            return true;
        }

        return !$this->_items[$name]->isEmpty();
    }

    /**
     * Unsetowanie właściwości.
     * 
     * @param string $name
     */
    public function __unset($name) {
        unset($this->_items[$name]);
    }

    /**
     * Tworzy nowy obiekt w taki sposób aby miał wskaźnik na swojego rodzica oraz
     * roota.
     * 
     * @param string $name Nazwa podobiektu
     * @return \static
     */
    protected function _createObject($name) {
        $item          = new static();
        $item->_name   = $name;
        $item->_parent = $this;
        $item->_root   = $this->_root;
        return $item;
    }

    /**
     * Sprawdza czy podana wartość jest instancją self'a.
     * 
     * @param mixed $value
     * @return boolean
     */
    public function isSelf($value) {
        return $value instanceof self;
    }

    /**
     * Sprawdza czy podana wartość jest instancją static'a.
     * 
     * @param mixed $value
     * @return boolean
     */
    public function isStatic($value) {
        return $value instanceof static;
    }

    /**
     * Sprawdza czy bieżący poziom jest pusty. Poziom NIE JEST pusty wtedy gdy
     * jego wartością nie jest obiekt klasy self lub istnieje chociaż jeden podelement
     * zawierający taką wartość.
     * 
     * @return boolean
     */
    public function isEmpty() {
        return $this->length() == 0;
    }

    /**
     * Zwraca liczbę elementów dla danego poziomu razem z wszystkimi podelementami
     * (liczy również puste elementy, tj. takie, które nie mają przypisanej 
     * żadnej wartości, ale istnieją jako pewien klucz).
     * 
     * @return int
     */
    public function count() {
        $numberOfItems = count($this->_items);
        if ($numberOfItems > 0) {
            foreach ($this->_items as $item) {
                $numberOfItems += $item->count();
            }
        }

        return $numberOfItems;
    }

    /**
     * Zwraca liczbę niepustych podelementów bieżącego poziomu. Sprawdzenie czy
     * obiekt jest pusty odbywa się przy pomocy metody isEmpty.
     * 
     * @return integer
     */
    public function length() {
        $numberOfItems = count($this->_items);
        foreach ($this->_items as $item) {
            if ($this->isSelf($item) && $item->isEmpty()) {
                $numberOfItems--;
            }
        }

        return $numberOfItems;
    }

    /**
     * Zwraca nazwę bieżącego poziomu lub null jeśli znajdujemy się w roocie.
     * 
     * @return string
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Zwraca tablicę ustawionych elementów dla bieżącego poziomu w ich
     * niezmienionej formie, tj. jako array.
     * 
     * @return array
     */
    public function getItems() {
        return $this->_items;
    }

    /**
     * Sprawdza czy bieżący poziom posiada ustawione podelementy.
     * 
     * @return boolean
     */
    public function hasItems() {
        return !empty($this->_items);
    }

    /**
     * Sprawdza czy bieżący element jest root'em.
     * 
     * Jako że każdy podelement root'a musi zawierać wskaźnik na swojego rodzica
     * root'em jest ten element, który go nie ma.
     * 
     * @return boolean
     */
    public function isRoot() {
        return null === $this->_parent;
    }

    /**
     * Zwraca obiekt rodzica danego poziomu. 
     * Umożliwia również pobranie rodzica oddalonego o pewną liczbę poziomów.
     * DODATKOWO jeżeli argument jest stringiem - poszukuje rodzica o podanej nazwie.
     * 
     * @param int|string $levelsUp  Ile poziomów w górę chcemy się wybrać lub
     *                              nazwa poszukiwanego elementu rodzica.
     * 
     * @return static|null          Zwraca znaleziony obiekt rodzica lub null
     *                              jeśli nie zostanie odnaleziony.
     */
    public function parent($levelsUp = 1) {
    if((!is_int($levelsUp) || $levelsUp < 1) && (!is_string($levelsUp) || empty($levelsUp))) {
        throw new IOException('Incorrect $levelsUp param');
    }

    $parent = $this->_parent;
    if (is_int($levelsUp)) {
        // poszukiwanie rodzica po liczbie poziomów
        for ($i = 1; $i < $levelsUp; $i++) {
            if ($parent && !$parent->isRoot()) {
                $parent = $parent->parent();
            } else {
                $parent = null;
                break;
            }
        }
    } else {
        // poszukiwanie rodzica po jego nazwie
        while (!$parent->isRoot()) {
            if ($parent->getName() === $levelsUp) {
                return $parent;
            }
            $parent = $parent->parent();
        }
        $parent = null;
    }

    return $parent;
}

/**
 * Zwraca obiekt root'a.
 * 
 * @return static
 */
public function root() {
    if (!$this->_root) {
        $this->_root = $this;
    }

    return $this->_root;
}

/**
 * Łączy bieżący obiekt z innym obiektem tego samego typu nadpisując
 * istniejące klucze.
 * 
 * @param static $obj
 * @throws IOException
 */
public function merge($obj) {
    if (!$this->isSelf($obj)) {
        throw new IOException('$value has to be an instance of ObjectModelBase');
    }

    foreach ($obj as $name => $item) {
        $this->{$name} = $item;
    }

    return $this;
}

/**
 * Czyści cały obiekt ze wszystkich ustawionych zmiennych.
 * @return ObjectModelBase
 */
public function clear() {
    foreach ($this as $key => $value) {
        unset($this->$key);
    }

    return $this;
}

}
