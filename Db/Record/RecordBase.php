<?php

namespace Skinny\Db\Record;

/**
 * Description of RecordBase
 *
 * @author Daro
 */
abstract class RecordBase {

    /**
     * Nazwa głównej tabeli, w której przechowywany jest wiersz (rekord)
     * @var string
     */
    protected $_tableName;

    /**
     * Tablica przechowująca nazwę (lub nazwy) kolumn klucza podstawowego
     * @var array
     */
    protected $_idColumn;

    /**
     * Okresla czy identyfikator tabeli ma domyslną wartość (np. autoincrement) <br/>
     * Jeżeli konstruktor klasy w polu identyfikatora przekazuje tablicę oznacza to, że PK nie ma wartości domyślnej
     * @var boolean 
     */
    protected $_idColumnHasDefault = false;

    /**
     * Identyfikator wiersza w tabeli głównej (tablica asocjacyjna klucz => wartość)
     * @var array
     */
    protected $_idValue;

    /**
     * Własny identyfikator (gdy nie jest używany autoincrement) - (tablica asocjacyjna klucz => wartość)
     * @var array
     */
    protected $_customId;

    /**
     * Określa, które kolumny należy odczytać pobierając rekord z tabeli głównej.
     * @var array
     */
    protected $_allowedColumns = array('*');

    /**
     * Określa, które kolumny ze zbioru danych rekordu nie należą do tabeli głównej oraz których kolumn nie należy z niej pobierać.
     * @var array
     */
    protected $_disallowedColumns = array();

    /**
     * Zawiera informację o kolumnach zakodowanych JSON'em, które mają byc automatycznie odkodowane
     * @var array
     */
    protected $_jsonColumns = array();
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
     * @param string|array $idColumn nazwa kolumny przechowującej id w tabeli głónej
     * - Jeżeli $idColumn jest stringiem oznacza to, że klucz główny dla tej tabeli ma domyślną wartość (np. autoincrement)
     * - Jeżeli $idColumn jest array'em oznacza to, że klucz główny nie ma wartości domyslnej <br/>
     * i przy tworzeniu nowych rekordów najpierw należy ustawić identyfikator poprzez metodę "setId". <br/>
     * Jeżeli tabela nie posiada wartości domyślnej w kluczu głównym, w konstuktorze <b>TRZEBA</b> podać $idColumn jako array <br/>
     * (np. ['identifier'], lub ['id1', 'id2'] dla kluczy wielopolowych)
     */
    public function __construct($mainTable, $idColumn = 'id', $data = array()) {
        \Skinny\Exception::throwIf(self::$db === null, new \Skinny\Db\DbException('Database adaptor used by record is not set'));

        if (!is_array($idColumn)) {
            $idColumn = [$idColumn];
            $this->_idColumnHasDefault = true;
        }
        $this->_idColumn = $idColumn;
        $this->_tableName = $mainTable;

        if (!empty($data)) {
            $this->set($data);
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
            foreach ($this->_idColumn as $col) {
                $primary .= $col . ", ";
            }
        }

        return get_class() . ': ' . $this->_tableName . ' (' . substr($primary, 0, -2) . ')';
    }

    /**
     * Pobiera identyfikator wiersza z tabeli głównej.
     * Jeżeli primary key jest wielopolowy to zwróci tablicę asocjacyjną (klucz => wartość) w przeciwnym wypadku wartość identyfikatora.
     * @return mixed identyfikator rekordu lub tablica asocjacyjna
     * @assert () == null
     */
    public function getId() {
        if (count($this->_idColumn) === 1) {
            return $this->_idValue[$this->_idColumn[0]];
        } else {
            return $this->_idValue;
        }
    }

    /**
     * Ustawia własny identyfikator (np. gdy id nie jest autoincrement)
     * @param mixed $id
     */
    public function setId($id) {
        if (null !== $this->_idValue) {
            throw new record\exception("Identifier already set");
        }
        $this->_customId = $this->_validateIdentifier($id);
    }

    /**
     * Pobiera nazwę kolumny (lub tablicę nazw gdy PK jest wielopolowy) przechowującej identyfikator wiersza w tabeli głównej
     * @return string
     */
    public static function getIdCol() {
        $obj = new static();
        if (count($obj->_idColumn) === 1) {
            return $obj->_idColumn[0];
        } else {
            return $obj->_idColumn;
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
     * Pobiera dane rekordu
     * Koduje ustawione pola w _jsonColumns do JSONA
     * Usuwa kolumny niedozwolone
     * Jeżeli allowedColumns != * to wybiera tylko te
     * @param boolean $mainTable czy dane mają się tyczyć tylko tabeli głównej rekordu
     * @return array dane
     */
    private function _getData() {
//        $getPublicFields = create_function('$obj', 'return get_object_vars($obj);');
        $data = \Skinny\ObjectHelper::getPublicProperties($this); //$getPublicFields($this);

        foreach ($this->_idColumn as $column) {
            unset($data[$column]);
        }

        if (!in_array("*", $this->_allowedColumns)) {
            $data2 = [];
            foreach ($this->_allowedColumns as $column) {
                if (key_exists($column, $data)) {
                    $data2[$column] = $data[$column];
                }
            }
            $data = $data2;
            unset($data2);
        }

        foreach ($this->_disallowedColumns as $column) {
            unset($data[$column]);
        }

        foreach ($this->_jsonColumns as $column) {
            if (isset($data[$column])) {
                $data[$column] = json_encode($data[$column]);
                $this->$column = json_decode($data[$column]); // wartości od razu powinny mieć taką formę jak przy odczycie z bazy - czyli tablice powinny być obiektami
            }
        }

        return $data;
    }

    public function toArray() {
        return $this->_getData();
    }

    /**
     * Wstawia dane z wiersza do tabeli i aktualizuje id
     * @return boolean informacja o powodzeniu
     */
    public function insert() {
        $data = $this->_getData();
        return $this->_insert($data);
    }

    /**
     * Wstawia podane dane do tabeli i aktualizuje id
     * @param array $data dane
     * @return boolean informacja o powodzeniu
     */
    final protected function _insert($data) {
        if (null !== $this->_customId) {
            foreach ($this->_idColumn as $col) {
                $data[$col] = $this->_customId[$col];
            }
            self::$db->insert($this->_tableName, $data);
            $this->_idValue = $this->_customId;
            $this->_customId = null;
        } else {
            self::$db->insert($this->_tableName, $data);
            $this->_idValue = $this->_validateIdentifier(self::$db->lastInsertId($this->_tableName, $this->_idColumn[0]));
        }
        return true;
    }

    /**
     * Aktualizuje rekord w tabeli
     * @return boolean informacja o powodzeniu
     */
    public function update() {
        $data = $this->_getData();
        return $this->_update($data);
    }

    /**
     * Aktualizuje rekord w tabeli podanymi danymi
     * @param array $data dane
     * @return boolean informacja o powodzeniu
     */
    final protected function _update($data) {
        if (null !== $this->_idValue) {
            self::$db->update($this->_tableName, $data, $this->_getWhere());
        }
        return true;
    }

    /**
     * Zapisuje rekord w tabeli wykonując insert lub update w zależności, czy istnieje id rekordu.
     * @param boolean $updateFromDb Gdy ustawione na true funkcja załaduje do rekordu wszystkie dane z bazy 
     * (przydatne gdy niektóre kolumny w bazie mają swoje domyslne wartości i nie są ustawione przy tworzeniu nowego rekordu,
     * wtedy aby ich używać jako pola rekordu należy te wartości z bazy i załadować do obiektu).
     * @return boolean informacja o powodzeniu
     */
    public function save($updateFromDb = false) {
        if (null === $this->_idValue) {
            if (!$this->_idColumnHasDefault) {
                throw new record\exception("Identifier has not been set");
            }
            $result = $this->insert();
        } else {
            $result = $this->update();
        }

        if ($result && $updateFromDb) {
            $this->_load($this->_idValue);
        }

        return $result;
    }

    /**
     * Usuwa rekord z tabeli
     * @return boolean informacja o powodzeniu
     */
    public function delete() {
        if (null !== $this->_idValue) {
            self::$db->delete($this->_tableName, $this->_getWhere());
            return true;
        }
        return false;
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
        foreach ($this->_idColumn as $col) {
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
                ->from($this->_tableName, $this->_allowedColumns);
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
        $class_name = get_called_class();
        $obj = new $class_name();
        if ($obj->_load($id)) {
            return $obj;
        } else {
            return null;
        }
    }

    /**
     * Dodaje do obiektu elementy znajdujące się w tablicy asocjacyjnej (wykluczając te znajdujące się w disallowedColumns)
     * @param array $data
     * @return boolean
     */
    public function set($data) {
        if (empty($data)) {
            return false;
        }
        if (!is_array($data)) {
            $data = (array) $data;
        }

        foreach ($data as $key => $value) {
            if (!in_array($key, $this->_disallowedColumns)) {
                if (in_array($key, $this->_jsonColumns) && !is_object($value) && !is_array($value)) {
                    try {
                        $value = json_decode($value);
                    } catch (\Exception $e) {
                        
                    }
                }
                $this->$key = $value;
            }
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

        // select
        $select = $this->_getSelect();
        $where = $this->_getWhere($id);
        foreach ($where as $key => $value) {
            $select->where($key, $value);
        }
        $data = self::$db->fetchRow($select);

        if (!$data) {
            return false;
        }

        //usuwamy z danych
        foreach ($this->_idColumn as $col) {
            unset($data[$col]);
        }

        foreach ($this->_disallowedColumns as $column) {
            unset($data[$column]);
        }

        // ustawiamy dane
        $this->_idValue = $id;
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        $this->_decodeJsonColumns($this, $this->_jsonColumns);

        return true;
    }

    /**
     * Dekoduje kolumny JSON i przypisuje odpowiednią wartość przez referencję do wskazanego obiektu
     * @param object $obj
     * @param array $columns
     */
    private function _decodeJsonColumns(&$obj, $columns) {
        // odkodowanie kolumn przechowujących dane w formacie JSON
        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (isset($obj->$column) && ($encoded = json_decode($obj->$column))) {
                    $obj->$column = $encoded;
                }
            }
        }
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
        if (!$data)
            return $result;

        foreach ($data as $row) {
            // nowy obiekt z id
            $obj = new static();
            $obj->_idValue = [];
            foreach ($this->_idColumn as $column) {
                $obj->_idValue[$column] = $row[$column];
            }
            $result[] = $obj;

            // usuwamy niechciane kolumny
            foreach ($this->_idColumn as $column) {
                unset($row[$column]);
            }
            foreach ($this->_disallowedColumns as $column) {
                unset($row[$column]);
            }

            // i przypisujemy ich wartości do obiektu
            foreach ($row as $key => $value) {
                $obj->$key = $value;
            }

            $this->_decodeJsonColumns($obj, $this->_jsonColumns);
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
        foreach ($obj->_idColumn as $column) {
            if (!key_exists($column, $assocArray)) {
                throw new record\exception("Invalid column set for primary key");
            }
            $obj->_idValue[$column] = $assocArray[$column];
            unset($assocArray[$column]);
        }

        $obj->set($assocArray);

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
            throw new record\exception('No column specified');
        }

        $array = [];
        if (!empty($records)) {
            foreach ($records as $record) {
                if ($record instanceof self) {
                    if (in_array($col, $record->_idColumn)) {
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
     * @throws record\exception
     */
    private function _validateIdentifier($id) {
        if (!is_array($id)) {
            if (count($this->_idColumn) !== 1) {
                throw new record\exception("Invalid identifier for multi-column primary key");
            }
            $id = [$this->_idColumn[0] => $id];
        } else {
            foreach ($this->_idColumn as $column) {
                if (!isset($id[$column])) {
                    throw new record\exception("Incomplete identifier for multi-column primary key");
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
        } catch (record\exception $e) {
            return false;
        }
        return true;
    }

}
