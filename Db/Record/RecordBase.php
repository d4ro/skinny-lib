<?php

namespace Skinny\Db\Record;

use Skinny\DataObject\Store;

/**
 * Description of RecordBase
 *
 * @author Daro
 */
abstract class RecordBase extends \Skinny\DataObject\DataBase {

    /**
     * Nazwa głównej tabeli, w której przechowywany jest wiersz (rekord)
     * @var string
     */
    protected $_tableName;

    /**
     * Tablica przechowująca nazwy kolumn klucza podstawowego
     * @var array
     */
    protected $_idColumns;

    /**
     * Tablica przechowująca nazwy kolumn automatycznie inkrementujących się (AUTO_INCREMENT lub sekwencje)
     * @var array
     */
    protected $_autoIdColumns = [];

    /**
     * Identyfikator wiersza w tabeli głównej (tablica asocjacyjna klucz => wartość)
     * @var array
     */
    protected $_idValue;

    /**
     * Określa, które kolumny należy odczytać pobierając rekord z tabeli głównej.
     * @var array
     */
    protected $_columns = null;

    /**
     * Określa, które kolumny ze zbioru danych rekordu mają nie być odczytywane z bazy danych.
     * @var array
     */
    protected $_readingDisabledColumns = [];

    /**
     * Określa, które kolumny ze zbioru danych rekordu mają nie być zapisywane do bazy danych.
     * @var array
     */
    protected $_writingDisabledColumns = [];

    /**
     * Zawiera informację o kolumnach zakodowanych JSON'em, które mają byc automatycznie odkodowane
     * @var array
     */
    protected $_jsonColumns = [];

    /**
     * Określa, które kolumny z kluczem obcym traktować jako rekord
     * @var array
     */
    protected $_recordColumns = [];

    /**
     * Określa wirtualne kolumny będące kolekcją rekordów
     * @var array
     */
    protected $_collectionVirtualColumns = [];

    /**
     * Przechowuje opcje rekordu
     * @var Store
     */
    protected $_config;

    /**
     * Czy rekord ma swój odpowiednik w bazie danych
     * @var boolean
     */
    protected $_exists;

    /**
     * Czy rekord został zmodyfikowany po ostatniej synchronizacji z bazą
     * @var boolean
     */
    protected $_isModified;

    /**
     * Czy rekord jest w trakcie procesu zapisywania
     * @var boolean
     */
    protected $_isSaving;

    /**
     * Połączenie do bazy danych
     * @var \Zend_Db_Adapter_Pdo_Mysql
     * @todo Uniezależnienie od Zend_Db
     */
    protected static $db;

    public static function getDb() {
        return self::$db;
    }

    public static function setDb($db) {
        // TODO: sprawdzenie typu
        self::$db = $db;
    }

    /**
     * Konstruktor rekordu
     * Klasa rozszerzająca musi podać rozszerzając nazwę tabeli głównej, w której znajduje się rekord.
     * Klasa rozszerzająca musi udostępnić bezargumentowy konstruktor o dostępności public.
     * @param string $mainTable nazwa tabeli, w której znajdują się główne dane rekordu
     * @param string|array $idColumns nazwa kolumny przechowującej id w tabeli głównej
     * - Jeżeli $idColumn jest stringiem oznacza to, że klucz główny dla tej tabeli ma domyślną wartość (np. autoincrement)
     * - Jeżeli $idColumn jest array'em oznacza to, że klucz główny nie ma wartości domyslnej <br/>
     * i przy tworzeniu nowych rekordów najpierw należy ustawić identyfikator poprzez metodę "setId". <br/>
     * Jeżeli tabela nie posiada wartości domyślnej w kluczu głównym, w konstuktorze <b>TRZEBA</b> podać $idColumn jako array <br/>
     * (np. ['identifier'], lub ['id1', 'id2'] dla kluczy wielopolowych)
     * @param array $data dane startowe rekordu
     * @param Store $options opcje:
     * - isIdAutoincrement: czy jednokolumnowy klucz główny ma być traktowany jako auto ID, domyślnie true
     * - isAutoRefreshForbidden: czy ma być wyłączone automatyczne pobieranie rekordu z bazy po inserach oraz update'ach, domyślnie false
     */
    public function __construct($mainTable, $idColumns = 'id', $data = array(), $options = null) {
        \Skinny\Exception::throwIf(self::$db === null, new \Skinny\Db\DbException('Database adaptor used by record is not set'));
        \Skinny\Exception::throwIf($options !== null && !($options instanceof Store), new \InvalidArgumentException('Param $options is not instance of Store'));

        if (null === $options) {
            $options = new Store();
        }

        $this->_config = $options;
        $this->_exists = false;
        $this->_isModified = false;
        $this->_isSaving = false;

        if (!is_array($idColumns)) {
            $idColumns = [$idColumns];
            if ($this->_config->isIdAutoincrement(true)) {
                $this->_autoIdColumns = $idColumns;
            }
        }

        $this->_idColumns = $idColumns;
        $this->_tableName = $mainTable;

        if (!empty($data)) {
            $this->importData($data);
        }
    }

    /**
     * Stwierdza, czy 
     * @return type
     */
    public function exists($checkInDatabase = false) {
        if ($checkInDatabase) {
            
        }

        return $this->_exists;
    }

    /**
     * Stwierdza, czy rekord został modyfikowany po ostatniej synchronizacji z bazą.
     * Nowe rekordy, które nie zostały jeszcze wprowadzone do bazy są zawsze "zmodyfikowane".
     * @return boolean
     */
    public function isModified() {
        return $this->_isModified;
    }

    /**
     * Stwierdza, czy rekord jest w trakcie procesu zapisywania.
     * @return boolean
     */
    public function isSaving() {
        return $this->_isSaving;
    }

    /**
     * Ustawia podaną kolumnę lub podane kolumny jak JSONowe.
     * Wartości w tych kolumnach będą traktowane jako obiekty zapisywane w bazie w notacji JSON.
     * @param string|array $columnNames
     */
    protected function _setJsonColumns($columnNames) {
        $columnName = (array) $columnName;
        foreach ($columnNames as $$columnName) {
            if (!array_key_exists($columnName, $this->_jsonColumns)) {
                $this->_jsonColumns[$columnName] = ['value' => null, 'hasValue' => false];
            }
        }
    }

    protected function _setRecordColumn($columnName, $recordClassName, array $ids) {
        $this->_recordColumns[$columnName] = ['value' => null, 'hasValue' => false, 'recordClassName' => $recordClassName, 'ids' => $ids];
    }

    protected function _setCollectionVirtualColumn($columnName, $recordClassName, array $where, $collectionClassName = null) {
        $this->_collectionVirtualColumns[$columnName] = ['value' => null, 'recordClassName' => $recordClassName, 'where' => $where, 'collectionClassName' => $collectionClassName];
    }

    protected function _getColumns() {
        if (null === $this->_columns) {
            $structure = $this->_getTableStructure($this->_tableName);
            $this->_columns = array_keys($structure);
            user_error('Performance issue: Record columns have not been specified. Had to describe table.', E_NOTICE);
        }

        return $this->_columns;
    }

    protected function _getTableStructure($tableName) {
        return self::$db->describeTable($tableName);
    }

    public function &__get($name) {
        if (array_key_exists($name, $this->_collectionVirtualColumns)) {
            // TODO
        }

        if (!array_key_exists($name, $this->_data)) {
            return null;
        }

        if (array_key_exists($name, $this->_recordColumns)) {
            if (!$this->_recordColumns[$name]['hasValue']) {
                // $ids ma odpowiedniki tam => tu [on1 => ja1, on2 => ja2]
                // u mnie jest [ja1 => 1, ja2 => 2, ja3 => 3]
                // chcę uzyskać [on1 => 1, on2 => 2]
                $ids = $this->_recordColumns[$name]['ids'];
                foreach ($ids as $key => $value) {
                    $ids[$key] = $this->_data[$value];
                }

                try {
                    $this->_recordColumns[$name]['value'] = null;
                    $this->_recordColumns[$name]['hasValue'] = true;
                    $ids = $this->_validateIdentifier($ids);
                    $this->_recordColumns[$name]['value'] = call_user_func(array($this->_recordColumns[$name]['recordClassName'], 'get'), $ids);
                } catch (Exception $ex) {
                    // niepowodzenie pobrania danych
                }
            }

            return $this->_recordColumns[$name]['value'];
        }

        if (array_key_exists($name, $this->_jsonColumns)) {
            if (!$this->_jsonColumns[$name]['hasValue']) {
                $this->_jsonColumns[$name]['value'] = json_decode($this->_data[$name]);
                $this->_jsonColumns[$name]['hasValue'] = true;
            }

            return $this->_jsonColumns[$name]['value'];
        }

        return parent::__get($name);
    }

    public function __set($name, $value) {
        $this->_isModified = true;
        $setData = true;

        if (array_key_exists($name, $this->_jsonColumns)) {
            $this->_jsonColumns[$name]['hasValue'] = false; //json_decode($value, true);
        }

        if (array_key_exists($name, $this->_recordColumns)) {
            if ($value instanceof self) {
                $this->_recordColumns[$name]['value'] = $value;
                $this->_recordColumns[$name]['hasValue'] = true;
                $setData = false;
                // TODO: pobrać ID i wpisać w to pole $this->_data[$name] = ???
                throw new Exception('not implemented');
            } else {
                $this->_recordColumns[$name]['hasValue'] = false;
            }
        }

        if ($setData) {
            parent::__set($name, $value);
        }
    }

    /**
     * Tesktowa reprezentacja obiektu
     * @return string
     */
    public function __toString() {
        $primary = "";
        if (!empty($this->_idValue)) {
            foreach ($this->_idValue as $col => $id) {
                $primary .= $col . " = " . $id . ", ";
            }
        } else {
            foreach ($this->_idColumns as $col) {
                $primary .= $col . ", ";
            }
        }

        return get_class() . ': ' . $this->_tableName . ' (' . substr($primary, 0, -2) . ')';
    }

    /**
     * Wewnętrzna metoda pobierająca wartość klucza podstawowego wiersza z tabeli głównej.
     * @return mixed
     */
    protected function _getId() {
        if (count($this->_idColumns) === 1) {
            return $this->_idValue[$this->_idColumns[0]];
        } else {
            return $this->_idValue;
        }
    }

    /**
     * Pobiera identyfikator wiersza z tabeli głównej.
     * Jeżeli primary key jest wielopolowy to zwróci tablicę asocjacyjną (klucz => wartość) w przeciwnym wypadku wartość identyfikatora.
     * @return mixed identyfikator rekordu lub tablica asocjacyjna
     * @assert () == null
     */
    public function getId() {
        return $this->_getId();
    }

    /**
     * Ustawia własny identyfikator (np. gdy id nie jest autoincrement).
     * Umożliwia usyawienie części klucza.
     * @param mixed $id
     */
    public function setId($id) {
        if (!is_array($id)) {
            if (count($this->_idColumns) == 1) {
                $this->_idValue[$this->_idColumns[0]] = $id;
            } else {
                throw new \Skinny\Db\DbException('Invalid identifier set for multi-column primary key.');
            }
        } else {
            foreach ($id as $key => $value) {
                if (in_array($key, $this->_idColumns)) {
                    $this->_idValue[$key] = $value;
                } else {
                    throw new \Skinny\Db\DbException('"' . $key . '" id not part of primary key.');
                }
            }
        }
    }

    /**
     * Pobiera identyfikator wiersza w postaci JSONa.
     */
    public function getIdAsString() {
        return json_encode($this->_idValue);
    }

    /**
     * Pobiera nazwę kolumny (lub tablicę nazw gdy PK jest wielopolowy) przechowującej identyfikator wiersza w tabeli głównej
     * @return string
     */
    public static function getIdColumn() {
        $obj = new static();
        if (count($obj->_idColumns) === 1) {
            return $obj->_idColumns[0];
        } else {
            return $obj->_idColumns;
        }
    }

    /**
     * Pobiera nazwę tabeli głównej wiersza.
     * @return string
     */
    public static function getTableName() {
        $obj = new static();
        return $obj->_tableName;
    }

    /**
     * Pobiera dane rekordu do zapisu do bazy danych
     * Koduje ustawione pola w _jsonColumns do JSONA
     * Usuwa kolumny niedozwolone
     * @param boolean $mainTable czy dane mają się tyczyć tylko tabeli głównej rekordu
     * @return array dane
     */
    private function _exportData() {
        $data = [];
        foreach ($this->_getColumns() as $column) {
            if (key_exists($column, $this->_data)) {
                $data[$column] = $this->_data[$column];
            }
        }

        // pozbywamy się tych kolumn w danych, które są w $this->_writingDisabledColumns
        foreach ($this->_writingDisabledColumns as $column) {
            unset($data[$column]);
        }

        // kolumny przechowujące wartości JSON kodujemy
        foreach ($this->_jsonColumns as $column) {
            if ($column['hasValue']) {
                $this->_data[$column] = $data[$column] = json_encode($column['value']); // wartości od razu powinny mieć taką formę jak przy odczycie z bazy - czyli tablice powinny być obiektami
            }
        }

        return $data;
    }

    /**
     * Pobiera dane rekordu w postaci tablicy.
     * @return array dane
     */
    public function toArray() {
        return $this->_exportData();
    }

    /**
     * Wstawia dane z wiersza do tabeli i aktualizuje id
     * @param boolean $refreshData czy ma pobrać rekord z bazy po wstawieniu
     * @return boolean informacja o powodzeniu
     */
    public function insert($refreshData = true) {
        $data = $this->_exportData();
        return $this->_insert($data, $refreshData && !$this->_config->isAutoRefreshForbidden(false, true));
    }

    /**
     * Wstawia podane dane do tabeli i aktualizuje id
     * @param array $data dane
     * @param boolean $refreshData czy ma pobrać rekord z bazy po wstawieniu
     * @return boolean informacja o powodzeniu
     */
    final protected function _insert($data, $refreshData) {
        $this->_isSaving = true;
        self::$db->insert($this->_tableName, $data);
        $id = $this->getLastInsertId();

        foreach ($this->_idColumns as $col) {
            if (array_key_exists($col, $id)) {
                continue;
            }

            if (array_key_exists($col, $this->_idValue)) {
                $id[$col] = $this->_idValue[$col];
            } elseif (isset($data[$col])) {
                $id[$col] = $data[$col];
            }
        }

        $this->_idValue = $this->_validateIdentifier($id);

        $this->_exists = true;
        $this->_isSaving = false;
        $this->_isModified = false;

        if ($refreshData) {
            $this->_load($this->_idValue);
        }

        return $this->_exists;
    }

    /**
     * Aktualizuje rekord w tabeli
     * @param boolean $refreshData czy ma pobrać rekord z bazy po aktualizacji
     * @param boolean $force czy ma wykonać update nawet wtedy, gdy rekord nie był modyfikowany
     * @return boolean informacja o powodzeniu
     */
    public function update($refreshData = true, $force = false) {
        if (!$this->_isModified && !$force) {
            return true;
        }

        $data = $this->_exportData();
        return $this->_update($data, $refreshData && !$this->_config->isAutoRefreshForbidden(false, true));
    }

    /**
     * Aktualizuje rekord w tabeli podanymi danymi
     * @param array $data dane
     * @param boolean $refreshData czy ma pobrać rekord z bazy po aktualizacji
     * @param boolean $force czy ma wykonać update nawet wtedy, gdy rekord nie był modyfikowany
     * @return boolean informacja o powodzeniu
     */
    final protected function _update($data, $refreshData) {
        if (null === $this->_idValue) {
            return false;
        }

        $id = $this->_validateIdentifier($this->_idValue);
        $this->_isSaving = true;

        $success = self::$db->update($this->_tableName, $data, $this->_getWhere());

        if ($success === 0) {
            $this->_exists = false;
            return false;
        }

        if ($success > 1) {
            throw new \Skinny\Db\DbException('Record identified by its primary key is ambiguous in table "' . $this->_tableName . '". Rows updated: ' . $success);
        }

        if ($refreshData) {
            $success = $this->_load($this->_idValue);
        }

        $this->_exists = true;
        $this->_isSaving = false;
        $this->_isModified = false;

        return (boolean) $success;
    }

    /**
     * Zapisuje rekord w tabeli wykonując insert lub update w zależności, czy istnieje id rekordu.
     * @param boolean $refreshData Gdy ustawione na true funkcja załaduje do rekordu wszystkie dane z bazy 
     * (przydatne gdy niektóre kolumny w bazie mają swoje domyslne wartości i nie są ustawione przy tworzeniu nowego rekordu,
     * wtedy aby ich używać jako pola rekordu należy te wartości z bazy i załadować do obiektu).
     * @return boolean informacja o powodzeniu
     */
    public function save($refreshData = true) {
        if (!$this->_exists) {
            $result = $this->insert($refreshData);
        } else {
            $result = $this->update($refreshData);
        }

        return $result;
    }

    /**
     * Usuwa rekord z tabeli
     * @return boolean informacja o powodzeniu
     */
    public function delete() {
        if (null === $this->_idValue) {
            return false;
        }

        self::$db->delete($this->_tableName, $this->_getWhere());
        return true;
    }

    /**
     * Usuwa grupę rekordów pasujących do zapytania where
     * @param Array
     * @return int - Zwraca liczbę usuniętych rekordów
     */
    public static function findAndDelete($where) {
        $obj = new static();

        if (empty($where)) {
            return 0;
        }

        return self::$db->delete($obj->_tableName, $where);
    }

    /**
     * Pobiera parametr where dla zapytań wybierających, usuwających i aktualizujących z wykorzystaniem podanego id lub zdefiniowanego w obiekcie.
     * @param int $id identyfikator rekordu
     * @return array|string część WHERE zapytania SQL
     */
    final protected function _getWhere($id = null) {
        if (null === $id) {
            $id = $this->_idValue;
        }

        $id = $this->_validateIdentifier($id);

        $where = [];
        foreach ($this->_idColumns as $col) {
            if (empty($id[$col])) {
                $where[] = self::$db->quoteIdentifier($col) . ' is null';
            } else {
                $where[self::$db->quoteIdentifier($col) . ' = ?'] = $id[$col];
            }
        }
        return $where;
    }

    /**
     * Zwraca obiekt zapytania select z przygotowanymi kolumnami i nazwą tabeli
     * @return Zend_Db_Select
     */
    final protected function _getSelect() {
        $select = self::$db->select()
                ->from($this->_tableName, $this->_getColumns());
        return $select;
    }

    /**
     * Zwraca obiekt zapytania select z przygotowanymi kolumnami i nazwą tabeli
     * @return Zend_Db_Select
     */
    public static function getSelect() {
        $obj = new static();
        return $obj->_getSelect();
    }

    /**
     * Zwraca obiekt reprezentujący rekord o podanym id
     * @param int|string|array $id
     * @return record
     */
    public static function get($id) {
        if (!is_array($id) && func_num_args() > 1) {
            $obj = new static();
            $id = array_combine($obj->_idColumns, func_get_args());
        }

        $obj = new static();
        if ($obj->_load($id)) {
            return $obj;
        } else {
            return null;
        }
    }

    /**
     * Dodaje do obiektu elementy znajdujące się w tablicy asocjacyjnej.
     * @param array $data
     * @return boolean
     */
    public function importData(array $data) {
        if (empty($data)) {
            return true;
        }

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->_collectionVirtualColumns)) {
                $this->_recordColumns[$key]['value'] = $value;
                continue;
            }

            if (array_key_exists($key, $this->_recordColumns)) {
                $this->_recordColumns[$key]['hasValue'] = false;
            }

            if (array_key_exists($key, $this->_jsonColumns)) {
                $this->_jsonColumns[$key]['hasValue'] = false;
            }

            $this->_data[$key] = $value;
        }

        return true;
    }

    /**
     * Ładuje dane rekordu do obiektu używając podanego id.
     * @param mixed $id identyfikator rekordu (lub tablica asocjacyjna "kolumna => identyfikator" gdy primary key jest wielopolowy)
     * @return boolean informacja o powodzeniu
     */
    protected function _load($id) {
        $id = $this->_validateIdentifier($id);
        $this->_exists = false;

        // select
        $select = $this->_getSelect();
        $where = $this->_getWhere($id);
        foreach ($where as $key => $value) {
            $select->where($key, $value);
        }

        $data = self::$db->fetchRow($select);
        if ($data) {
            // ustawiamy dane
            $this->_idValue = $id;

            foreach ($this->_readingDisabledColumns as $column) {
                unset($data[$column]);
            }

            foreach ($data as $key => $value) {
                $this->_data[$key] = $value;

                if (array_key_exists($key, $this->_recordColumns)) {
                    $this->_recordColumns[$key]['hasValue'] = false;
                }

                if (array_key_exists($key, $this->_jsonColumns)) {
                    $this->_jsonColumns[$key]['hasValue'] = false;
                }
            }

            $this->_exists = true;
        }

        return $this->_exists;
    }

    /**
     * Pobiera wszystkie rekordy spełniające podane warunki w odpowiedniej kolejności.
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return array tablica obiektów rekordów będących rezultatem zapytania
     */
    public static function find($where = null, $order = null, $limit = null, $offset = null) {
        $class_name = get_called_class();
        $obj = new $class_name();

        // select
        $select = $obj->_getSelect();
        if ($order) {
            $select->order($order);
        }
        if ($limit) {
            $select->limit($limit, $offset);
        }
        if ($where) {
            if (is_array($where)) {
                foreach ($where as $k => $v) {
                    if (!is_numeric($k)) {
                        $select->where($k, $v);
                    } else {
                        $select->where($v);
                    }
                }
            } else {
                $select->where($where);
            }
        }

        return $obj->_select($select);
    }

    /**
     * Pobiera pierwszy rekord spełniający podane warunki
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @return record|null pierwszy rekord spełniający warunki lub null
     */
    public static function findOne($where = null, $order = null, $offset = null) {
        $result = self::find($where, $order, 1, $offset);
        if (!empty($result)) {
            return $result[0];
        }
        return null;
    }

    /**
     * Zwraca liczbę rekordów spełniających podane warunki
     * @param type $where część zapytania WHERE
     * @return integer
     */
    public static function count($where = null) {
        $select = self::$db->select()
                ->from(['t' => static::getTableName()], ['COUNT(1)']);
        if ($where) {
            if (is_array($where)) {
                foreach ($where as $k => $v) {
                    if (!is_numeric($k)) {
                        $select->where($k, $v);
                    } else {
                        $select->where($v);
                    }
                }
            } else {
                $select->where($where);
            }
        }

        return self::$db->fetchOne($select);
    }

    /**
     * Pobiera wszystkie rekordy będące rezultatem zapytania SELECT.
     * Ważne jest, aby zapytanie wybierało faktycznie rekordy tyczące się tego obiektu oraz wszelkie dodatkowe użyte kolumny były zawarte w _disallowedColumns.
     * W przeciwnym wypadku funckje zapisujące rekord się nie powiodą.
     * UWAGA! To sprawa programisty, czy select wybiera właściwe kolumny z właściwych tabel. Nie ma co do tego żadnej walidacji!
     * @param string|Zend_Db_Select $select zapytanie SELECT do bazy
     * @return array tablica obiektów rekordów będących rezultatem zapytania
     */
    public static function select($select) {
        $class_name = get_called_class();
        $obj = new $class_name();
        return $obj->_select($select);
    }

    /**
     * Pomocnicza funkcja pobierająca wszystkie rekordy spełniające warunki selecta.
     * @param string|Zend_Db_Select $select zapytanie SELECT do bazy
     * @return array tablica obiektów rekordów będących rezultatem zapytania
     */
    private function _select($select) {
        $data = self::$db->fetchAll($select);

        // czy są dane
        $result = array();
        if (!$data) {
            return $result;
        }

        foreach ($data as $row) {
            // nowy obiekt z id
            $obj = new static();
            $obj->_idValue = [];
            foreach ($this->_idColumns as $column) {
                $obj->_idValue[$column] = $row[$column];
            }

            // usuwamy niechciane kolumny
            foreach ($this->_readingDisabledColumns as $column) {
                unset($row[$column]);
            }

            // i przypisujemy ich wartości do obiektu
            foreach ($row as $key => $value) {
                $obj->_data[$key] = $value;
            }

            if (array_key_exists($key, $obj->_recordColumns)) {
                $obj->_recordColumns[$key]['hasValue'] = false;
            }

            if (array_key_exists($key, $obj->_jsonColumns)) {
                $obj->_jsonColumns[$key]['hasValue'] = false;
            }

            $result[] = $obj;
        }

        return $result;
    }

    /**
     * Konwertuje wyniki z zapytania do bazy na tablicę rekordów
     * @param type $arrayOfAssocArrays Tablica tablic asocjacyjnych
     * @return array
     */
    public static function toRecords(array $arrayOfAssocArrays) {
        $array = [];
        foreach ($arrayOfAssocArrays as $assocArray) {
            $array[] = static::toRecord($assocArray);
        }

        return $array;
    }

    /**
     * Konwertuje tablicę asocjacyjną (np. wiersz z bazy danych) na obiekt record
     * @param array $assocArray
     * @return \static
     */
    public static function toRecord(array $assocArray) {
        $obj = new static();

        $obj->_idValue = [];
        foreach ($obj->_idColumns as $column) {
            if (!key_exists($column, $assocArray)) {
                throw new RecordException("Invalid column set for primary key");
            }
            $obj->_idValue[$column] = $assocArray[$column];
            unset($assocArray[$column]);
        }

        $obj->importData($assocArray);

        return $obj;
    }

    /**
     * Pobiera wszystkie wartości wybranej kolumny z rekordów w kolekcji w formie tablicy.
     * Jeżeli jakaś kolumna nie istnieje lub ma pustą wartość nie zostanie zwrócona w tablicy.
     * @param array $records Tablica rekordów
     * @return array Tablica wartości z wybranej kolumny
     */
    public static function fetchCol($col, array $records) {
        if (!$col) {
            throw new RecordException('No column specified');
        }

        $array = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                if ($record instanceof self) {
                    if (in_array($col, $record->_idColumns)) {
                        $id = $record->getId();
                        if (is_array($id)) {
                            $id = @$id[$col];
                        }
                        if (!empty($id)) {
                            $array[] = $id;
                        }
                    } elseif (!empty($record->$col)) {
                        $array[] = $record->$col;
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Walidacja identyfikatora
     * Jeżeli identyfikator nie jest tablicą i przejdzie poprawnie walidację, zostaje zwrócony w formie tablicy.
     * @param mixed $id
     * @throws RecordException
     */
    protected function _validateIdentifier($id) {
        if (!is_array($id)) {
            if (count($this->_idColumns) !== 1) {
                throw new RecordException("Invalid identifier for multi-column primary key");
            }
            $id = [$this->_idColumns[0] => $id];
        } else {
            foreach ($this->_idColumns as $column) {
                if (!isset($id[$column])) {
                    throw new RecordException("Incomplete identifier for multi-column primary key");
                }
            }
        }
        return $id;
    }

    /**
     * Sprawdza czy identyfikator jest prawidłowy
     * @param type $id
     * @return boolean
     */
    public static function isIdValid($id) {
        $self = new static();
        try {
            $self->_validateIdentifier($id);
        } catch (RecordException $e) {
            return false;
        }
        return true;
    }

    public function getLastInsertId($idCol = null) {
        if (null !== $idCol) {
            return $this->getLastInsertIdHelper($idCol);
        }

        $result = [];
        foreach ($this->_autoIdColumns as $col) {
            $lastId = $this->getLastInsertIdHelper($col);
            $result[$col] = $lastId;
        }
        return $result;
    }

    protected function getLastInsertIdHelper($idCol/* , $tableName = null */) {
        /* if (null === $tableName) {
          $tableName = $this->_tableName;
          } */

        return self::$db->lastInsertId(/* $tableName */$this->_tableName, $idCol);
    }

}
