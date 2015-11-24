<?php

namespace Skinny\Db\Record;

/**
 * Klasa bazowa kolekcji rekordów. Może funkcjonować niezależnie od konkretnej implementacji.
 * Zawiera metody potrzebne do pracy nad kolekcją rekordów umożliwiając masowe przypisywanie i pobieranie danych z rekordów oraz wykonywanie ich i na nich metod oraz innych operacji.
 */
class RecordCollection extends \Skinny\DataObject\ArrayWrapper {

    const IDX_PLAIN = 0;
    const IDX_ID = 1;
    const IDX_TBL_ID = 2;
    const IDX_HASH = 3;

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
    protected $_isStrictTypeCheckEnabled;

    /**
     * Aktualnie używany indeks domyślny
     * @var int
     */
    protected $_useIndex;

    /**
     * Indeksy danych
     * @var array
     */
    protected $_idx;

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
        $this->_idx = [
            self::IDX_PLAIN => [],
            self::IDX_ID => [],
            self::IDX_TBL_ID => [],
            self::IDX_HASH => [],
        ];
        $this->useIndex(self::IDX_PLAIN);

        $this->_isStrictTypeCheckEnabled = $strictTypeCheck;
        $this->addRecords($collection, true);

        parent::__construct($collection);
    }

    /**
     * Magiczne wywołanie metody na kolekcji wywołuje ją na każdym rekordzie z osobna.
     * 
     * @param string $name nazwa metody
     * @param array $arguments argumenty metody
     * @return array tablica rezultatów metody
     */
    public function &__call($name, $arguments) {
        $result = $this->call($name, $arguments);
        return $result;
    }

    /**
     * Ustawia aktualnie używany indeks. Możliwe wartości:
     *       self::IDX_PLAIN    =>  indeks kolejny, inkrementowany, numerowany od 0,
     *       self::IDX_ID       =>  indeks na podstawie ID rekordu,
     *       self::IDX_TBL_ID   =>  indeks na podstawie nazwy tabeli i ID rekordu,
     *       self::IDX_HASH     =>  indeks na podstawie unikalnego hasha rekordu.
     * 
     * @param int $index
     * @todo walidacja parametrów
     */
    public function useIndex($index) {
        $this->_useIndex = $index;
    }

    /**
     * Konwertuje kolekcję na tablicę z zachowaniem indeksów aktualnie ustawionego typu.
     * 
     * @return array
     */
    public function toArray() {
        $result = [];
        foreach ($this->_idx[$this->_useIndex] as $key => $recordNum) {
            $record = $this->_data[$recordNum];
            $result[$key] = $record;
        }
        return $result;
    }

    /**
     * Sprawdza, czy array zawiera prawidłowe obiekty rekordów.
     * 
     * @param array $collection
     * @param boolean $strict sprawdza, czy wszystkie rekordy są tego samego typu
     * @param boolean $throw
     * @return boolean
     */
    protected function _checkArrayType(array $collection, $strict = true, $throw = false) {
        $exception = new InvalidRecordException('Record Collection contains elements of invalid type');
        $error = false;

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
        }

        \Skinny\Exception::throwIf($error === true && $throw === true, $exception);
        return !$error;
    }

    /**
     * Ustawia restrykcyjność homogeniczności typu rekordów.
     * 
     * @param boolean $value czy homogeniczność ma zostać zachowana
     */
    public function setStrictTypeCheck($value) {
        $this->_isStrictTypeCheckEnabled = $value;
    }

    /**
     * Pobiera ustawienie homogeniczności typu rekordów.
     * 
     * @return boolean
     */
    public function getStrictTypeCheck() {
        return $this->_isStrictTypeCheckEnabled;
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
     * @param boolean $throwOnBadType czy ma wyrzucić wyjątek, gdy typ rekordu nie zgadza się z typem kolekcji
     * @return boolean czy udało sie dodać rekordy do kolekcji
     */
    public function addRecords($records, $throwOnBadType = false) {
        if ($records instanceof RecordCollection) {
            $records = $records->_data;
        }

        if ($this->_checkArrayType($records, $this->_isStrictTypeCheckEnabled, $throwOnBadType)) {
            $this->_addToIndex($records);
            return true;
        } else {
            return false;
        }
    }

    protected function _addToIndex(array $records) {
        foreach ($records as $record) {
            /* @var $record RecordBase */
            $this->_data[] = $record;
            $key = \Skinny\DataObject\ArrayWrapper::lastInsertKey($this->_data);
            $this->_idx[self::IDX_PLAIN][$key] = $key;
            $this->_idx[self::IDX_ID][$record->getIdAsString(false, true)] = $key;
            $this->_idx[self::IDX_TBL_ID][$record->getIdAsString(true, true)] = $key;
            $this->_idx[self::IDX_HASH][$record->createRandomHash()] = $key;
        }
    }

    /**
     * Tworzy nowy rekord w kolekcji i zwraca do niego referencję.
     * Wymaga, aby typ rekordu w kolekcji został zdefiniowany.
     * 
     * @param array $data
     * @return RecordBase stworzony rekord
     */
    public function create(array $data = []) {
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
            foreach ($this->_idx[$this->_useIndex] as $key => $recordNum) {
                $record = $this->_data[$recordNum];
                $result[$key] = $callback($record, $key);
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
            $collection->_isStrictTypeCheckEnabled = $this->_isStrictTypeCheckEnabled;
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
        foreach ($this->_idx[$this->_useIndex] as $key => $recordNum) {
            $record = $this->_data[$recordNum];
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
    public function &__get($name) {
        $result = array();
        foreach ($this->_idx[$this->_useIndex] as $key => $recordNum) {
            $record = $this->_data[$recordNum];
            $result[$key] = $record->$name;
        }
        return $result;
    }

    /**
     * Dodaje pojedynczy rekord do kolekcji.
     * 
     * @param RecordBase $record
     */
    public function add(RecordBase $record) {
        $this->addRecords([$record]);
    }

    /**
     * Wstawia rekord w podaną pozycję indeksu kolejnego (plain) kolekcji.
     * 
     * @param \Skinny\Db\Record\RecordBase $record
     * @param int $offset
     */
    public function insertRecord(RecordBase $record, $offset) {
        if (key_exists($offset, $this->_data)) {
            foreach ($this->_idx[self::IDX_ID] as $key => $value) {
                if ($value == $offset) {
                    unset($this->_idx[self::IDX_ID][$key]);
                }
            }
            foreach ($this->_idx[self::IDX_TBL_ID] as $key => $value) {
                if ($value == $offset) {
                    unset($this->_idx[self::IDX_TBL_ID][$key]);
                }
            }
            foreach ($this->_idx[self::IDX_HASH] as $key => $value) {
                if ($value == $offset) {
                    unset($this->_idx[self::IDX_HASH][$key]);
                }
            }
        }

        $this->_data[$offset] = $record;

        $this->_idx[self::IDX_PLAIN][$offset] = $offset;
        $this->_idx[self::IDX_ID][$record->getIdAsString(false, true)] = $offset;
        $this->_idx[self::IDX_TBL_ID][$record->getIdAsString(true, true)] = $offset;
        $this->_idx[self::IDX_HASH][$record->createRandomHash()] = $offset;
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
    public function addFind($where = null, $order = null, $limit = null, $offset = null) {
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
        $collection->addFind($where, $order, $limit, $offset);
        return $collection;
    }

    /**
     * Metoda używana przy isset($this[$offset]).
     * 
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset) {
        return key_exists($offset, $this->_idx[$this->_useIndex]);
    }

    /**
     * Pobiera rekord z aktualnie używanego indeksu spod podanego klucza.
     * 
     * @param int|string $name
     * @param mixed $default
     * @return RecordBase
     */
    public function &get($name, $default = null) {
        if (key_exists($name, $this->_idx[$this->_useIndex])) {
            return $this->_data[$this->_idx[$this->_useIndex][$name]];
        }

        return $default;
    }

    /**
     * Ustawia rekord pod podanym kluczem aktualnie używanego indeksu.
     * 
     * @param string|int $name klucz indeksu
     * @param \Skinny\Db\Record\RecordBase $value
     * @throws InvalidRecordException
     * @throws RecordException
     * @todo Do sprawdzenia czy podmiana rekordu nie powoduje bałąganu w indeksach
     */
    public function set($name, $value) {
        if (!($value instanceof RecordBase)) {
            throw new InvalidRecordException('Value is not a record');
        }

        if (key_exists($name, $this->_idx[$this->_useIndex])) {
            $key = $this->_idx[$this->_useIndex][$name];
            $this->_data[$key] = $value;

            $this->_idx[self::IDX_PLAIN][$key] = $key;
            $this->_idx[self::IDX_ID][$value->getIdAsString(false, true)] = $key;
            $this->_idx[self::IDX_TBL_ID][$value->getIdAsString(true, true)] = $key;
            $this->_idx[self::IDX_HASH][$value->createRandomHash()] = $key;
        } elseif ($this->_useIndex === self::IDX_PLAIN) {
            $this->insertRecord($value, $name);
        } else {
            throw new RecordException("Index '$name' has not been found and not in plain index mode. Cannot create and insert this record.");
        }
    }

    /**
     * Metoda używana przy unset($this[$offset]).
     * 
     * @param mixed $offset
     * @return boolean
     */
    public function offsetUnset($offset) {
        if (key_exists($offset, $this->_idx[$this->_useIndex])) {
            $key = $this->_idx[$this->_useIndex][$offset];
            $this->unsetAt($key);
        }
    }

    /**
     * Pobiera rekord z podanej pozycji iterowanej od 0.
     * 
     * @param int $offset
     * @return RecordBase
     */
    public function getAt($offset) {
        return (key_exists($offset, $this->_data) ? $this->_data[$offset] : null);
    }

    /**
     * Ustawia rekord na podanej pozycji.
     * 
     * @param int $offset
     * @param RecordBase $value
     */
    public function setAt($offset, RecordBase $value) {
        $this->insertRecord($value, $offset);
    }

    /**
     * Usuwa rekord z kolekcji, z podanej pozycji.
     * 
     * @param int $offset
     */
    public function unsetAt($offset) {
        if (key_exists($offset, $this->_data)) {
            unset($this->_data[$offset]);
            unset($this->_idx[self::IDX_PLAIN][$offset]);

            foreach ($this->_idx[self::IDX_ID] as $key => $value) {
                if ($value == $offset) {
                    unset($this->_idx[self::IDX_ID][$key]);
                }
            }
            foreach ($this->_idx[self::IDX_TBL_ID] as $key => $value) {
                if ($value == $offset) {
                    unset($this->_idx[self::IDX_TBL_ID][$key]);
                }
            }
            foreach ($this->_idx[self::IDX_HASH] as $key => $value) {
                if ($value == $offset) {
                    unset($this->_idx[self::IDX_HASH][$key]);
                }
            }
        }
    }

    /**
     * Serializuje kolekcję do formatu JSON łącznie z rekordami w niej się znajdującymi.
     * 
     * @return string
     */
    public function toJson() {
        return json_encode($this->_data);
    }

}
