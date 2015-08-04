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
     * @param array $records
     */
    public function addRecords(array $records) {
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
            return $result;
        } else {
            throw new \BadFunctionCallException('Callback is not a function.');
        }
    }

    public function call($method, array $params = array()) {
        $result = array();
        foreach ($this->_data as $key => $record) {
            $result[$key] = call_user_method_array($method, $record, $params);
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

}
