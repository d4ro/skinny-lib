<?php

namespace Skinny\Db\Record;

/**
 * Klasa bazowa kolekcji rekordów. Może funkcjonować niezależnie od konkretnej implementacji.
 * Zawiera metody potrzebne do pracy nad kolekcją rekordów umożliwiając masowe przypisywanie i pobieranie danych z rekordów oraz wykonywanie ich i na nich metod oraz innych operacji.
 */
class RecordCollection extends \Skinny\DataObject\ArrayWrapper {

    /**
     * Połączenie do bazy danych
     * @var \Zend_Db_Adapter_Pdo_Mysql
     * @todo Uniezależnienie od Zend_Db
     */
    protected static $db;

    /**
     * Nazwa klasy obsługującej rekordy w kolekcji
     * @var string
     */
    protected $_recordClassName;

    /**
     * Czy typy rekordów mają być dokładnie sprawdzane
     * @var boolean
     */
    protected $_isStrictTypeCheck;

    /**
     * Pobiera połączenie do bazy danych.
     * 
     * @return \Zend_Db_Adapter_Pdo_Mysql
     */
    public static function getDb() {
        return self::$db;
    }

    /**
     * Ustawia połączenie do bazy danych.
     * 
     * @param \Zend_Db_Adapter_Pdo_Mysql $db
     */
    public static function setDb(\Zend_Db_Adapter_Pdo_Mysql $db) {
        self::$db = $db;
    }

    /**
     * Konstruktor kolekcji. Przyjmuje opcjonalną tablicę rekordów, jako podstawa kolekcji.
     * 
     * @param array $collection kolekcja początkowa
     * @param boolean $strictTypeCheck czy kolekcja wejściowa ma być sprawdzona pod kątem homogeniczności typu
     */
    public function __construct(array $collection = array(), $strictTypeCheck = true) {
        $this->_isStrictTypeCheck = $strictTypeCheck;
        $this->_checkArrayType($collection, $strictTypeCheck, true);
        parent::__construct($collection);
    }

    /**
     * MAgiczne wywołanie metody na kolekcji wywołuje ją na każdym rekordzie z osobna.
     * 
     * @param string $name nazwa metody
     * @param array $arguments argumenty metody
     * @return array tablica rezultatów metody
     */
    public function __call($name, $arguments) {
        return $this->call($name, $arguments);
    }

    /**
     * Sprawdza, czy array zawiera prawidłowe obiekty rekordów.
     * 
     * @param array $collection
     * @param boolean $strict sprawdza, czy wszystkie rekordy są tego samego typu
     * @param boolean $throw
     * @return boolean
     */
    protected function _checkArrayType(array &$collection, $strict = true, $throw = false) {
        $exception = new InvalidRecordException('Record Collection contains elements of invalid type');
        $error = false;
        $result = [];

        if (!empty($collection)) {
            $first = $collection[key($collection)];
        } else {
            return true;
        }

        $firstRecordClass = get_class($first);
        if ($strict) {
            $this->_recordClassName = $firstRecordClass;
        }

        foreach ($collection as $element) {
            // sprawdzenie, czy obiekt jest rekordem
            if (!($element instanceof RecordBase)) {
                $error = true;
                break;
            }

            // sprawdzenie, czy rekord jest tego samego typu, co pierwszy z kolekcji
            if ($strict && get_class($element) !== $firstRecordClass) {
                $error = true;
                break;
            }

            // przepisujemy rekord do nowego arraya z odpowiednim kluczem
            $result[$element->getIdAsString()] = $element;
        }

        \Skinny\Exception::throwIf($error === true && $throw === true, $exception);
        $collection = $result;
        return !$error;
    }

    /**
     * Ustawia restrykcyjność homogeniczności typu rekordów.
     * 
     * @param boolean $isStrict czy homogeniczność ma zostać zachowana
     */
    public function setStrictTypeCheck($isStrict) {
        $this->_isStrictTypeCheck = $isStrict;
    }

    /**
     * Pobiera ustawienie homogeniczności typu rekordów.
     * 
     * @return boolean
     */
    public function getStrictTypeCheck() {
        return $this->_isStrictTypeCheck;
    }

    /**
     * Pobiera nazwę klasy rekordów, dla których przeznaczona jest instancja kolekcji.
     * 
     * @return string
     */
    public function getRecordClassName() {
        return $this->_recordClassName;
    }

    /**
     * Ustawia nazwę klasy rekordów, dla których przeznaczona jest instancja kolekcji.
     * 
     * @param string $className
     */
    public function setRecordClassName($className) {
        \Skinny\Exception::throwIf(isset($this->_recordClassName), new \Skinny\Db\DbException('Record type has been already set for this collection'));

        $this->_recordClassName = $className;
    }

    /**
     * Dodaje rekordy do kolekcji.
     * 
     * @param array|RecordCollection $records
     */
    public function addRecords($records) {
        if ($records instanceof RecordCollection) {
            $records = $records->_data;
        }

        $this->_checkArrayType($records, $this->_isStrictTypeCheck);
        foreach ($records as $key => $value) {
            $this->_data[$key] = $value;
        }
    }

    /**
     * Tworzy nowy rekord w kolekcji i zwraca do niego referencję.
     * Wymaga, aby typ rekordu w kolekcji został zdefiniowany.
     * 
     * @param array $data
     * @return RecordBase stworzony rekord
     */
    public function create(array $data = null) {
        \Skinny\Exception::throwIf(null === $this->_recordClassName, new RecordException('Record class name is not defined'));

        $record = new $this->_recordClassName($data);
        $this->addRecords([$record]);
        return $record;
    }

    /**
     * Pobiera identyfikatory wszystkich rekordów kolekcji.
     * 
     * @return array
     */
    public function getIds() {
        return $this->call('getId');
    }

    /**
     * Zapisuje wszystkie rekordy.
     * 
     * @return array
     */
    public function save() {
        // TODO: optymalizacja - pobranie zapytań do bazy i wykonanie ich w jednym query
        return $this->call('save');
    }

    /**
     * Usuwa wszystkie rekordy z kolekcji, nie czyści z nich kolekcji.
     * 
     * @return type
     */
    public function delete() {
        // TODO: optymalizacja
        return $this->call('remove');
    }

    /**
     * Ustawia podaną właściwość każdemu rekordowi w kolekcji.
     * 
     * @param string $property
     * @param mixed $value
     */
    public function apply($property, $value) {
        foreach ($this->_data as $record) {
            $record->$property = $value;
        }
    }

    /**
     * Uruchamia callback dla każdego rekordu w kolekcji. Przechytuje wartość zwracaną.
     * 
     * @param \Closure $callback
     * @return array
     * @throws \BadFunctionCallException
     */
    public function forEachRecord($callback) {
        $result = array();
        if ($callback instanceof \Closure) {
            foreach ($this->_data as $key => $record) {
                $result[$key] = $callback($record);
            }
        } else {
            throw new \BadFunctionCallException('Callback is not a function.');
        }
        return $result;
    }

    /**
     * Usuwa z wszystkich rekordów kolekcji kolumny o podanej nazwie.
     * 
     * @param array $columnsToRemove Kolumny, które mają zostać usunięte z każdego rekordu kolekcji
     */
    public function removeColumns(array $columnsToRemove = []) {
        $this->forEachRecord(function($record) use ($columnsToRemove) {
            foreach ($columnsToRemove as $column) {
                unset($record->{$column});
            }
        });
    }

    /**
     * Usuwa z wszystkich rekordów kolekcji wszystkie kolumny, oprócz tych 
     * podanych jako argument tej metody.
     * 
     * @param array $columnsToLeave Kolumny, które mają zostać w rekordzie
     */
    public function removeColumnsExcept(array $columnsToLeave = []) {
        $this->forEachRecord(function($record) use ($columnsToLeave) {
            foreach ($record as $key => $value) {
                if (!in_array($key, $columnsToLeave)) {
                    unset($record->{$key});
                }
            }
        });
    }

    /**
     * Filtruje kolekcję usuwając z niej rekordy, przy których callback zwróci false.
     * 
     * @param \Closure $callback
     * @return \static nowa, przefiltrowana kolekcja
     * @throws \BadFunctionCallException
     */
    public function filter($callback) {
        $result = [];
        if ($callback instanceof \Closure) {
            foreach ($this->_data as $id => $record) {
                if ($callback($record)) {
                    $result[$id] = $record;
                }
            }
            $collection = new static();
            $collection->_isStrictTypeCheck = $this->_isStrictTypeCheck;
            $collection->_recordClassName = $this->_recordClassName;
            $collection->_data = $result;
        } else {
            throw new \BadFunctionCallException('Callback is not a function.');
        }
        return $collection;
    }

    /**
     * Uruchamia metodę dla każdego rekordu.
     * 
     * @param string $method
     * @param array $params
     * @return array
     */
    public function call($method, array $params = array()) {
        $result = array();
        foreach ($this->_data as $key => $record) {
            $result[$key] = call_user_func_array([$record, $method], $params);
        }
        return $result;
    }

    /**
     * Przypisuje wartość każdemu rekordowi z kolekcji.
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $this->apply($name, $value);
    }

    /**
     * Pobiera wartość właściwości z każdego rekordu kolekcji i wzraca w postaci tablicy.
     * 
     * @param string $name właściwość do pobrania
     * @return array
     */
    public function __get($name) {
        $result = array();
        foreach ($this->_data as $key => $record) {
            $result[$key] = $record->$name;
        }
        return $result;
    }

    /**
     * Pobiera do kolekcji wszystkie rekordy spełniające podane warunki.
     * Wymaga, aby nazwa klasy obsługiwanych rekordów była już ustawiona.
     * 
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return int ilość dodanych do kolekcji rekordów
     */
    public function AddFind($where = null, $order = null, $limit = null, $offset = null) {
        \Skinny\Exception::throwIf(empty($this->_recordClassName), new RecordException('Record class name has not been set for this record collection so find() cannot operate.'));
        $records = call_user_func([$this->_recordClassName, 'findArray'], $where, $order, $limit, $offset);
        $this->addRecords($records);
        return count($records);
    }

    /**
     * Tworzy nową kolekcję rekordów na podstawie rekordów znalezionych przy podanych warunkach.
     * Wymaga, aby nazwa klasy obsługiwanych rekordów była ustawiona przez konstruktor kolekcji.
     * 
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return \static
     */
    public static function find($where = null, $order = null, $limit = null, $offset = null) {
        $collection = new static();
        $collection->AddFind($where, $order, $limit, $offset);
        return $collection;
    }

    /**
     * Metoda używana przy isset($this[$offset])
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset) {
        if (is_numeric($offset) && ($offset = (int) $offset) >= 0) {
            return null !== $this->getAt($offset);
        } else {
            return parent::offsetExists($offset);
        }
    }

    /**
     * Metoda używana przy return $this[$offset]
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        if (is_numeric($offset) && ($offset = (int) $offset) >= 0) {
            return $this->getAt($offset);
        } else {
            return parent::offsetGet($offset);
        }
    }

    /**
     * Metoda używana przy $this[$offset] = $value
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        if (is_numeric($offset) && ($offset = (int) $offset) >= 0) {
            return $this->setAt($offset, $value);
        } else {
            return parent::offsetSet($offset, $value);
        }
    }

    /**
     * Metoda używana przy unset($this[$offset])
     * @param mixed $offset
     * @return boolean
     */
    public function offsetUnset($offset) {
        if (is_numeric($offset) && ($offset = (int) $offset) >= 0) {
            return $this->unsetAt($offset);
        } else {
            return parent::offsetUnset($offset);
        }
    }

    /**
     * Pobiera rekord z podanej pozycji iterowanej od 0.
     * @param int $offset
     * @return RecordBase
     */
    public function getAt($offset) {
        $keys = array_keys($this->_data);
        if (!isset($keys[$offset])) {
            return null;
        }
        return $this->_data[$keys[$offset]];
    }

    /**
     * Ustawia rekord na podanej pozycji. Pozycja musi być już zajmowana przez rekord.
     * @param int $offset
     * @param RecordBase $value
     */
    public function setAt($offset, RecordBase $value) {
        $keys = array_keys($this->_data);
        if (isset($keys[$offset])) {
            $this->_data[$keys[$offset]] = $value;
        }
    }

    /**
     * Usuwa rekord z kolekcji, z podanej pozycji.
     * @param int $offset
     */
    public function unsetAt($offset) {
        $keys = array_keys($this->_data);
        if (isset($keys[$offset])) {
            unset($this->_data[$keys[$offset]]);
        }
    }

}
