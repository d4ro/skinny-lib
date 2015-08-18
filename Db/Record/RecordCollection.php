<?php

namespace Skinny\Db\Record;

/**
 * Description of RecordCollection
 *
 * @author Daro
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
     * Pobiera połączenie do bazy danych
     * @return \Zend_Db_Adapter_Pdo_Mysql
     */
    public static function getDb() {
        return self::$db;
    }

    /**
     * Ustawia połączenie do bazy danych
     * @param \Zend_Db_Adapter_Pdo_Mysql $db
     */
    public static function setDb($db) {
        // TODO: sprawdzenie typu
        self::$db = $db;
    }

    public function __construct(array $collection = array(), $strictTypeCheck = true) {
        $this->_isStrictTypeCheck = $strictTypeCheck;
        $this->_checkArrayType($collection, $strictTypeCheck, true);
        parent::__construct($collection);
    }

    public function __call($name, $arguments) {
        return $this->call($name, $arguments);
    }

    /**
     * Sprawdza, czy array zawiera prawidłowe obiekty rekordów
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

    public function setStrictTypeCheck($isStrict) {
        $this->_isStrictTypeCheck = $isStrict;
    }

    public function getStrictTypeCheck() {
        return $this->_isStrictTypeCheck;
    }

    public function getRecordClassName() {
        return $this->_recordClassName;
    }

    public function setRecordClassName($className) {
        \Skinny\Exception::throwIf(isset($this->_recordClassName), new \Skinny\Db\DbException('Record type has been already set for this collection'));

        $this->_recordClassName = $className;
    }

    /**
     * Dodaje rekordy do kolekcji
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

    public function getIds() {
        return $this->call('getId');
    }

    public function save() {
        // TODO: optymalizacja - pobranie zapytań do bazy i wykonanie ich w jednym query
        return $this->call('save');
    }

    public function delete() {
        $ids = $this->getIds();

        self::$db->delete($ids, $where);
    }

    public function apply($property, $value) {
        foreach ($this->_data as $record) {
            $record->$property = $value;
        }
    }

    public function forEachRecord($callback) {
        $result = array();
        if ($callback instanceof \Closure) {
            foreach ($this->_data as $record) {
                $result[$key] = $callback($record);
            }
        } else {
            throw new \BadFunctionCallException('Callback is not a function.');
        }
        return $result;
    }

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

    public function call($method, array $params = array()) {
        $result = array();
        foreach ($this->_data as $key => $record) {
            $result[$key] = call_user_func_array([$record, $method], $params);
        }
        return $result;
    }

    public function __set($name, $value) {
        $this->apply($name, $value);
    }

    public function __get($name) {
        $result = array();
        foreach ($this->_data as $key => $record) {
            $result[$key] = $record->$name;
        }
        return $result;
    }

    /**
     * Pobiera do kolekcji wszystkie rekordy spełniające podane warunki.
     * Wymaga, aby nazwa klasy obsługiwanych rekordów była już ustawiona/
     * 
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return int ilość dodanych do kolekcji rekordów
     */
    public function find($where = null, $order = null, $limit = null, $offset = null) {
        \Skinny\Exception::throwIf(empty($this->_recordClassName), new RecordException('Record class name has not been set for this record collection so find() cannot operate.'));
        $records = call_user_func([$this->_recordClassName, 'findArray'], $where, $order, $limit, $offset);
        $this->addRecords($records);
        return count($records);
    }

    public function offsetExists($offset) {
        if (is_numeric($offset) && ($offset = (int) $offset) >= 0) {
            return null !== $this->getAt($offset);
        } else {
            return parent::offsetExists($offset);
        }
    }

    public function offsetGet($offset) {
        if (is_numeric($offset) && ($offset = (int) $offset) >= 0) {
            return $this->getAt($offset);
        } else {
            return parent::offsetGet($offset);
        }
    }

    public function offsetSet($offset, $value) {
        if (is_numeric($offset) && ($offset = (int) $offset) >= 0) {
            return $this->setAt($offset, $value);
        } else {
            return parent::offsetSet($offset, $value);
        }
    }

    public function offsetUnset($offset) {
        if (is_numeric($offset) && ($offset = (int) $offset) >= 0) {
            return $this->unsetAt($offset);
        } else {
            return parent::offsetUnset($offset);
        }
    }

    public function getAt($offset) {
        $keys = array_keys($this->_data);
        if (!isset($keys[$offset])) {
            return null;
        }
        return $this->_data[$keys[$offset]];

//        $data = $this->_data;
//        reset($data);
//        for ($i = 0; $i < $offset; $i++) {
//            if (!next($data)) {
//                return null;
//            }
//        }
//        return current($data);
    }

    public function setAt($offset, $value) {
        $keys = array_keys($this->_data);
        if (isset($keys[$offset])) {
            $this->_data[$keys[$offset]] = $value;
        }

//        $data = $this->_data;
//        reset($data);
//        for ($i = 0; $i < $offset; $i++) {
//            if (!next($data)) {
//                return null;
//            }
//        }
//        $this->_data[key($data)] = $value;
    }

    public function unsetAt($offset) {
        $keys = array_keys($this->_data);
        if (isset($keys[$offset])) {
            unset($this->_data[$keys[$offset]]);
        }

//        $data = $this->_data;
//        reset($data);
//        for ($i = 0; $i < $offset; $i++) {
//            if (!next($data)) {
//                return null;
//            }
//        }
//        unset($this->_data[key($data)]);
    }

}
