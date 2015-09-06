<?php

namespace Skinny\Db\Record;

use Skinny\DataObject\Store;

abstract class RecordBase extends \Skinny\DataObject\DataBase implements \JsonSerializable, \ArrayAccess, \IteratorAggregate {

    /**
     * Połączenie do bazy danych
     * @var \Zend_Db_Adapter_Pdo_Mysql
     * @todo Uniezależnienie od Zend_Db
     */
    protected static $db;

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
    protected $_idValue = [];

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
     * Określa kolumny będące kolekcją rekordów
     * @var array
     */
    protected $_collectionColumns = [];

    /**
     * Określa kolumny, które mają być filtrowane w specyficzny sposób
     * @var array
     */
    protected $_filteredColumns = [];

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

    public function getIterator() {
        return new \ArrayIterator($this->exportData());
    }

    /**
     * Pomocnicza funkcja pobierająca wszystkie rekordy spełniające warunki selecta.
     * 
     * @param \Zend_Db_Select $select zapytanie SELECT do bazy
     * @return array tablica obiektów rekordów będących rezultatem zapytania
     */
    private static function _select($select) {
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
            foreach ($obj->_idColumns as $column) {
                $obj->_idValue[$column] = $row[$column];
            }

            // usuwamy niechciane kolumny
            foreach ($obj->_readingDisabledColumns as $column) {
                unset($row[$column]);
            }

            // i przypisujemy ich wartości do obiektu
            $obj->_setData($row);
            $obj->_exists = true;
            $obj->_isModified = false;
            $obj->_isSaving = false;

            $result[] = $obj;
        }
        return $result;
    }

    public static function getDb() {
        return self::$db;
    }

    public static function setDb($db) {
        // TODO: sprawdzenie typu
        self::$db = $db;
    }

    /**
     * Pobiera nazwę tabeli głównej wiersza.
     * 
     * @return string
     */
    public static function getTableName() {
        $obj = new static();
        return $obj->_tableName;
    }

    /**
     * Pobiera nazwę kolumny (lub tablicę nazw gdy PK jest wielopolowy) przechowującej identyfikator wiersza w tabeli głównej.
     * 
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
     * Konstruktor rekordu
     * Klasa rozszerzająca musi podać rozszerzając nazwę tabeli głównej, w której znajduje się rekord.
     * Klasa rozszerzająca musi udostępnić bezargumentowy konstruktor o dostępności public.
     * 
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
        $this->_tableName = (string) $mainTable;

        if (!empty($data)) {
            $this->importData($data);
        }
    }

    public function &__get($name) {
        /* @var $collection RecordCollection */
        if (array_key_exists($name, $this->_collectionColumns)) {
            if (!$this->_collectionColumns[$name]['hasValue']) {
                $this->_buildCollectionColumn($name);
            }

            return $this->_collectionColumns[$name]['value'];
        }

        if (array_key_exists($name, $this->_recordColumns)) {
            if (!$this->_recordColumns[$name]['hasValue']) {
                // $identifier ma odpowiedniki tam => tu [on1 => ja1, on2 => ja2]
                // u mnie jest [ja1 => 1, ja2 => 2, ja3 => 3]
                // chcę uzyskać [on1 => 1, on2 => 2]
//                $identifier = $this->_recordColumns[$name]['identifier'];
//                foreach ($identifier as $key => $value) {
//                    $identifier[$key] = $this->getRawValue($value); //$this->_data[$value];
//                }

                $identifier = $this->_buildWhere($this->_recordColumns[$name]['identifier']);

                try {
                    $this->_recordColumns[$name]['value'] = null;
                    $this->_recordColumns[$name]['hasValue'] = true;
//                    $identifier = $this->_validateIdentifier($identifier);
                    $this->_recordColumns[$name]['value'] = call_user_func(array($this->_recordColumns[$name]['recordClassName'], 'get'), $identifier);
                } catch (Exception $ex) {
                    // niepowodzenie pobrania danych
                }
            }

            return $this->_recordColumns[$name]['value'];
        }

        if (!array_key_exists($name, $this->_data)) {
            return null;
        }

        if (array_key_exists($name, $this->_jsonColumns)) {
            if (!$this->_jsonColumns[$name]['hasValue']) {
                $this->_jsonColumns[$name]['value'] = json_decode($this->_data[$name], true);
                $this->_jsonColumns[$name]['hasValue'] = true;
            }

            return $this->_jsonColumns[$name]['value'];
        }

        if (array_key_exists($name, $this->_filteredColumns) && isset($this->_filteredColumns[$name]['getter'])) {
            return $this->_filteredColumns[$name]['getter'](parent::__get($name));
        }

        return parent::__get($name);
    }

    public function __set($name, $value) {
        $this->_isModified = true;
        $setData = true;

        if (array_key_exists($name, $this->_collectionColumns)) {
            $setData = false;
        }

        if (array_key_exists($name, $this->_recordColumns)) {
            if ($value instanceof self) {
                $this->_recordColumns[$name]['value'] = $value;
                $this->_recordColumns[$name]['hasValue'] = true;
                $setData = false;

                $identifier = $this->_recordColumns[$name]['identifier'];
                foreach ($identifier as $remoteCol => $localCol) {
                    if ($localCol === $name) {
                        $this->_data[$localCol] = $value->_idValue[$remoteCol]; // TODO do testowania
                    }
                }
            } else {
                $this->_recordColumns[$name]['hasValue'] = false;
            }
        }

        if (array_key_exists($name, $this->_jsonColumns)) {
            $this->_jsonColumns[$name]['hasValue'] = false; //json_decode($value, true);
        }

        if ($setData) {
            if (array_key_exists($name, $this->_filteredColumns) && isset($this->_filteredColumns[$name]['setter'])) {
                $value = $this->_filteredColumns[$name]['setter']($value);
            }

            parent::__set($name, $value);
        }
    }

    /**
     * Tesktowa reprezentacja obiektu
     * 
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
     * Generuje tablicę parametrów Where dla rekordów obcych na podstawie definicji. Definicja jest tablica, gdzie:
     * - kluczem jest zapytaniem WHERE dla bazy danych, gdzie wartość kolumny jest zastąpiona znakiem zapytania
     * - wartością może być:
     *   - int - traktowany jest jako ostateczna wartość wstawiona do zapytania
     *   - array - traktowany jest jako tablica ostatecznych wartości wtawionych do zapytania
     *   - string rozpoczynający się od ' - traktowany jest jako ostateczna wartość wstawiona do zapytania, a pierwszy znak jest usuwany
     *   - każdy pozostały string - traktowany jest nazwa kolumny z bieżącego rekordu, do zapytania wstawiana jest jego aktualna wartość w momencie wykonania zapytania
     *   - Closure - uruchamiany jest w momencie wykonania zapytania, a wartość, którą zwróci wstawiana jest do klucza w miejsce znaku zapytania
     * 
     * @param array $whereDefinition definicja, na podstawie której jest tworzony docelowy Where dla zapytania do bazy danych
     * @return array tablica warunkó Where do zapytania
     * @throws RecordException gdy wartość którejś definicji jest nieprawidłowa
     */
    private function _buildWhere($whereDefinition) {
        $where = [];

        foreach ($whereDefinition as $key => $value) {
            if (is_int($value) || is_array($value)) {
                // traktujemy dosłownie
            } elseif (is_string($value) && strlen($value) > 1) {
                // string
                if ($value[0] == '\'') {
                    // literal
                    $value = substr($value, 1);
                } else {
                    // nazwa kolumny
                    $value = $this->getRawValue($value);
                }
            } elseif ($value instanceof \Closure) {
                $value = $value();
            } else {
                throw new RecordException('Invalid value in foreign record/collection column "' . $name . '" in where set at "' . $key . '"');
            }

            $where[$key] = $value;
        }

        return $where;
    }

    /**
     * Tworzy kolekcję rekordów obcych na podstawie definicji wirtualnej kolumny.
     * Po utworzeniu kolumna jest dostępna do działania.
     * 
     * @param string $name nazwa wirtualnej kolumny
     */
    private function _buildCollectionColumn($name) {
        $recordClassName = $this->_collectionColumns[$name]['recordClassName'];
        $customCollectionClass = !empty($this->_collectionColumns[$name]['collectionClassName']);

        // instancjowanie kolekcji rekordów
        if ($customCollectionClass) {
            // TODO instancjowanie konkretnej kolekcji
            $collection = new $this->_collectionColumns[$name]['collectionClassName']();
            $recordClassName = $collection->getRecordClassName();
        }

        // budowanie zapytania
        $where = $this->_buildWhere($this->_collectionColumns[$name]['where']);

        // pobranie rekordów
        // przypisanie rekordów
        if ($customCollectionClass) {
            /* @var $records RecordCollection */
            $records = call_user_func([$recordClassName, 'findArray'], $where, $this->_collectionColumns[$name]['order'], $this->_collectionColumns[$name]['limit'], $this->_collectionColumns[$name]['offset']);
            $collection->addRecords($records);
        } else {
            /* @var $records RecordCollection */
            $collection = call_user_func([$recordClassName, 'find'], $where, $this->_collectionColumns[$name]['order'], $this->_collectionColumns[$name]['limit'], $this->_collectionColumns[$name]['offset']);
        }

        $this->_collectionColumns[$name]['value'] = $collection;
        $this->_collectionColumns[$name]['hasValue'] = true;
    }

    /**
     * Ustawia dane rekordu na podstawie arraya z danymi, gdzie klucz to nazwa kolumny, a wartością jest jej wartość.
     * Wewnętrzne ustawienie danych jest obojętne na status modyfikacji pól rekordu. Tego ustawienia powinna wykonać metoda korzystająca z _setRecordData().
     * 
     * @param array $data dane do ustawienia
     */
    protected function _setData(array $data, $useFiltering = false) {
        foreach ($data as $key => $value) {
            if (key_exists($key, $this->_collectionColumns)) {
                if (null === $value || $value instanceof RecordCollection) {
                    $this->_collectionColumns[$key]['value'] = $value;
                }

                continue;
            }

            if (key_exists($key, $this->_recordColumns)) {
                if ($value instanceof self) {
                    $this->_recordColumns[$key]['value'] = $value;
                    $this->_recordColumns[$key]['hasValue'] = true;

                    $identifier = $this->_recordColumns[$key]['identifier'];
                    foreach ($identifier as $remoteCol => $localCol) {
                        if ($localCol === $key) {
                            $this->_data[$localCol] = $value->_idValue[$remoteCol]; // TODO do testowania
                        }
                    }

                    continue;
                }

                $this->_recordColumns[$key]['hasValue'] = false;
            }

            if (key_exists($key, $this->_jsonColumns)) {
                $this->_jsonColumns[$key]['hasValue'] = false;
            }

            if ($useFiltering && key_exists($key, $this->_filteredColumns) && isset($this->_filteredColumns[$key]['setter']) && $this->_filteredColumns[$key]['setter'] instanceof \Closure) {
                $value = $this->_filteredColumns[$key]['setter']($value);
            }

            $this->_data[$key] = $value;
        }
    }

    /**
     * Wewnętrzna metoda pobierająca wartość klucza podstawowego wiersza z tabeli głównej.
     * 
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
     * Ustawia podaną kolumnę lub podane kolumny jak JSONowe.
     * Wartości w tych kolumnach będą traktowane jako obiekty zapisywane w bazie w notacji JSON.
     * 
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

    /**
     * Ustawia podaną kolumnę jako identyfikator dla innego rekordu. Umożliwia obsługę danego pola rekordu jako referencji do innego obiektu rekordu.
     * 
     * @param string $columnName nazwa kolumny (pola) rekordu, która ma być traktowana jako referencja do innego obiektu rekordu
     * @param string $recordClassName nazwa klasy rekordu obsługującego dane pole
     * @param array $identifier identyfikator zdalnego rekordu na podstawie danych bieżącego rekordu w formacie priKey1 => col1, priKey2 => col2
     */
    protected function _setRecordColumn($columnName, $recordClassName, array $identifier) {
        $this->_recordColumns[$columnName] = ['value' => null, 'hasValue' => false, 'recordClassName' => $recordClassName, 'identifier' => $identifier];
    }

    /**
     * Ustawia podane wirtualne pole rekordu jako kolekcję rekordów na podstawie podanego warunku where i typu rekordu lub kolekcji.
     * Warunek zapytania jest tablicą, gdzie:
     * - kluczem jest zapytaniem WHERE dla bazy danych, gdzie wartość kolumny jest zastąpiona znakiem zapytania
     * - wartością może być:
     *   - int - traktowany jest jako ostateczna wartość wstawiona do zapytania
     *   - array - traktowany jest jako tablica ostatecznych wartości wtawionych do zapytania
     *   - string rozpoczynający się od ' - traktowany jest jako ostateczna wartość wstawiona do zapytania, a pierwszy znak jest usuwany
     *   - każdy pozostały string - traktowany jest nazwa kolumny z bieżącego rekordu, do zapytania wstawiana jest jego aktualna wartość w momencie pobierania kolekcji
     *   - Closure - uruchamiany jest w momencie pobierania kolekcji, a wartość, którą zwróci wstawiana jest do zapytania
     * 
     * @param string $columnName nazwa nieistniejącego (wirtualnego) pola rekordu, które ma być obsługiwane jako kolekcja rekordów
     * @param array $where warunek zapytania dla pobrania rekordów kolekcji
     * @param string $recordClassName nazwa klasy rekordów kolekcji (musi zostać być podana, jeżeli nazwa klasy kolekcji nie została)
     * @param string $collectionClassName nazwa specyficznej klasy kolekcji rekordów (musi zostać być podana, jeżeli nazwa klasy rekordów nie została)
     * @param string $order opcjonalna kolejność wyników
     * @param string $limit opcjonalny limit ilości wyników
     * @param string $offset opcjonalne przesunięcie wyników
     */
    protected function _setCollectionColumn($columnName, array $where, $recordClassName = null, $collectionClassName = null, $order = null, $limit = null, $offset = null) {
        if (null === $recordClassName && null === $collectionClassName) {
            throw new \Skinny\Db\DbException('Invalid arguments while declaring collection virtual column "' . $columnName . '". Arguments $recordClassName and/or $collectionClassName must be set.');
        }

        $this->_collectionColumns[$columnName] = [
            'value' => null,
            'hasValue' => false,
            'recordClassName' => $recordClassName,
            'where' => $where,
            'collectionClassName' => $collectionClassName,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Ustawia specjalne filtrowanie podanej kolumny rekordu. Można podać getter i/lub setter.
     * Uruchamiane są w sytuacji pobrania lub ustawienia wartości podanej kolumny.
     * Jeżeli getter nie jest ustawiony, a wartość jest pobierana, zachowanie jest standardowe.
     * Analogicznie sytuacja ma się z setterem przy ustawieniu wartości.
     * @param string $columnName nazwa kolumny, któa ma posiadać specjalne filtrowanie
     * @param \Closure $getter funkcja filtrująca pobierane dane z kolumny
     * @param \Closure $setter funkcja filtrująca ustawiane dane do kolumny
     */
    protected function _setFilteredColumn($columnName, \Closure $getter = null, \Closure $setter = null) {
        $this->_filteredColumns[$columnName] = [
            'setter' => $setter,
            'getter' => $getter
        ];
    }

    /**
     * Zwraca nazwy obsługiwanych kolumn tabeli głównej bieżącego rekordu. Pola wirtualne nie są brane pod uwagę.
     * 
     * @param boolean $everything czy ma podać wszystkie kolumny zdefiniowane w rekordzie
     * @return array
     */
    public function getColumns($everything = false) {
        $columns = $this->_columns;
        if (null === $this->_columns) {
            $structure = $this->_getTableStructure($this->_tableName);
            $this->_columns = $columns = array_keys($structure);
            user_error('Performance issue: Record columns have not been specified. Had to describe table.', E_USER_NOTICE);
        }

        if ($everything) {
            $realColumns = array_keys($this->_data);
            $columns = array_merge($columns, $realColumns);
        }

        return $columns;
    }

    /**
     * Ustawia nazwy kolumn używanych w rekordzie.
     * 
     * @param array $columns
     */
    protected function _setColumns(array $columns) {
        $this->_columns = $columns;
    }

    /**
     * Pobiera strukturę tabeli o podanej nazwie w postaci tablicy. Kluczami tablicy są nazwy kolumn tabeli.
     * 
     * @param string $tableName
     * @return array
     */
    protected function _getTableStructure($tableName) {
        return self::$db->describeTable($tableName);
    }

    /**
     * Pobiera aktualne wartości zdefiniowanych kolumn złączoniowych identyfikatora rekordu obcego dla odpowiednich nazw kolumn rekordu bieżącego.
     * 
     * @param string $name
     * @return array
     */
    protected function _getIdValuesFormRecordColumn($name) {
        $result = [];
        $value = $this->_recordColumns[$name]['value'];
        $identifier = $this->_recordColumns[$name]['identifier'];

        foreach ($identifier as $remoteCol => $localCol) {
            if ($localCol === $name) {
                $result[$localCol] = $value->_idValue[$remoteCol]; // TODO do testowania
            }
        }

        return $result;
    }

    /**
     * Pobiera dane rekordu do zapisu do bazy danych
     * Koduje ustawione pola w _jsonColumns do JSONA
     * Pomija kolumny niedozwolone
     * 
     * @param boolean $everything czy ma nie pomijać kolumn spoza tabeli głównej
     * @return array dane
     */
    public function exportData($everything = false, $useFiltering = false) {
        $data = [];
        foreach ($this->getColumns($everything) as $column) {
            // ignorujemy kolumny, których mamy nie zapisywać do bazy
            if (in_array($column, $this->_writingDisabledColumns)) {
                continue;
            }

            // konwertujemy kolumny jsonowe, gdy jest w nich wartość
            if (array_key_exists($column, $this->_jsonColumns) && $this->_jsonColumns[$column]['hasValue']) {
                $this->_data[$column] = json_encode($this->_jsonColumns[$column]['value']);
            }

            // pobieramy ID z obcych rekordów, o ile są załadowane
            if (array_key_exists($column, $this->_recordColumns) && $this->_recordColumns[$column]['hasValue']) {
                $result = $this->_getIdValuesFormRecordColumn($column);
                foreach ($result as $key => $value) {
                    $this->_data[$key] = $value;
                }
            }

            if (key_exists($column, $this->_data)) {
                if ($useFiltering && array_key_exists($column, $this->_filteredColumns) && isset($this->_filteredColumns[$column]['getter'])) {
                    $value = $this->_filteredColumns[$column]['getter']($this->_data[$column]);
                } else {
                    $value = $this->_data[$column];
                }

                $data[$column] = $value;
            }
        }
        return $data;
    }

    /**
     * Stwierdza, czy rekord istnieje w bazie danych.
     * 
     * @param boolean $checkInDatabase czy ma sprawdzić, czy na pewno jest w bazie danych
     * @return type
     */
    public function exists($checkInDatabase = false) {
        if ($checkInDatabase) {
            $select = $this->_getSelectWhere();
            $select->limit(1);
            $sql = "SELECT EXISTS($select)";
            $result = self::$db->fetchOne($sql);
            $this->_exists = (boolean) $result;
        }

        return $this->_exists;
    }

    /**
     * Stwierdza, czy rekord został modyfikowany po ostatniej synchronizacji z bazą.
     * Nowe rekordy, które nie zostały jeszcze wprowadzone do bazy są zawsze "zmodyfikowane".
     * 
     * @return boolean
     */
    public function isModified() {
        // TODO: do przerobiena
        return $this->_isModified;
    }

    /**
     * Stwierdza, czy rekord jest w trakcie procesu zapisywania.
     * 
     * @return boolean
     */
    public function isSaving() {
        return $this->_isSaving;
    }

    /**
     * Pobiera identyfikator wiersza z tabeli głównej.
     * Jeżeli primary key jest wielopolowy to zwróci tablicę asocjacyjną (klucz => wartość) w przeciwnym wypadku wartość identyfikatora.
     * 
     * @return mixed identyfikator rekordu lub tablica asocjacyjna
     * @assert () == null
     */
    public function getId() {
        if (count($this->_idColumns) === 1) {
            return $this->_idValue[$this->_idColumns[0]];
        } else {
            return $this->getFullId();
        }
    }

    /**
     * Pobiera indentyfikator wiersza z tabeli głównej.
     * 
     * @return array klucz główny w postaci indeks = nazwa kolumny => wartość kolumny
     * @todo Optymalizacja / cache'owanie
     */
    public function getFullId() {
        $result = array();
        sort($this->_idColumns);
        foreach ($this->_idColumns as $col) {
            if (array_key_exists($col, $this->_idValue)) {
                $result[$col] = $this->_idValue[$col];
            } else {
                $result[$col] = isset($this->_data[$col]) ? $this->_data[$col] : null;
            }
        }

        return $result;
    }

    /**
     * Pobiera identyfikator wiersza w postaci unikalnego dla typu rekordu stringa.
     * 
     * @param boolean $includeTable czy ma dodać nazwę tabeli do klucza
     * @return string stringowa reprezentacja id
     */
    public function getIdAsString($includeTable = false, $randomHashAsNull = false) {
        $fullId = $this->getFullId();
        $result = $includeTable ? '"' . $this->_tableName . '":' : '';
        foreach ($fullId as $val) {
            if (null === $val) {
                $result .= ($randomHashAsNull ? $this->createRandomHash() : 'null') . ',';
            } else {
                $result .= '"' . htmlspecialchars($val) . '",';
            }
        }
        return substr($result, 0, strlen($result) - 1);
    }

    /**
     * Ustawia własny identyfikator (np. gdy id nie jest autoincrement).
     * Umożliwia usyawienie części klucza.
     * 
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
     * Pobiera dane rekordu w postaci tablicy.
     * Uwzględnia ustawione kolumny powiązane.
     * 
     * @return array dane
     */
    public function toArray() {
        $array = [];
        foreach($this as $key => $value) {
            $array[$key] = $this->{$key};
        }
        return $array;
//        return $this->exportData(true);
    }

    public function createRandomHash() {
        $length = 10;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Wstawia dane z wiersza do tabeli i aktualizuje ID.
     * 
     * @param boolean $refreshData czy ma pobrać rekord z bazy po wstawieniu
     * @return boolean informacja o powodzeniu
     */
    public function insert($refreshData = true) {
        $data = $this->exportData();
        return $this->_insert($data, $refreshData && !$this->_config->isAutoRefreshForbidden(false, true));
    }

    /**
     * Wstawia podane dane do tabeli i aktualizuje identyfikator.
     * 
     * @param array $data dane
     * @param boolean $refreshData czy ma pobrać rekord z bazy po wstawieniu
     * @return boolean informacja o powodzeniu
     */
    final protected function _insert($data, $refreshData) {
        $this->_isSaving = true;
        self::$db->insert($this->_tableName, $data);
        /* @var $id array */
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
            // pobierz dane z bazy
            $this->_load($this->_idValue);
        } else {
            // przynajmniej zaktualizuj dane z klucza głównego
            foreach ($this->_idValue as $key => $value) {
                $this->_data[$key] = $value;
            }
        }

        return $this->_exists;
    }

    /**
     * Aktualizuje rekord w tabeli przy pomocy zapytania UPDATE.
     * 
     * @param boolean $refreshData czy ma pobrać rekord z bazy po aktualizacji
     * @param boolean $force czy ma wykonać update nawet wtedy, gdy rekord nie był modyfikowany
     * @return boolean informacja o powodzeniu
     */
    public function update($refreshData = true, $force = false) {
        if (!$this->_isModified && !$force) {
            return true;
        }

        $data = $this->exportData();
        return $this->_update($data, $refreshData && !$this->_config->isAutoRefreshForbidden(false, true));
    }

    /**
     * Aktualizuje rekord w tabeli podanymi danymi przy pomocy zapytania UPDATE do bazy danych.
     * 
     * @param array $data dane
     * @param boolean $refreshData czy ma pobrać rekord z bazy po aktualizacji
     * @param boolean $force czy ma wykonać update nawet wtedy, gdy rekord nie był modyfikowany
     * @return boolean informacja o powodzeniu
     */
    final protected function _update($data, $refreshData) {
        if (null === $this->_idValue) {
            return false;
        }

        $this->_idValue = $this->_validateIdentifier($this->_idValue);
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
     * Pobiera wartość kolumny rekordu w takiej formie, jaka jest zapisywana do tabeli lub została ustawiona.
     * 
     * @param string $name nazwa kolumny (pola) rekordu
     * @return mixed
     */
    public function getRawValue($name) {
        if (array_key_exists($name, $this->_jsonColumns) && $this->_jsonColumns[$name]['hasValue']) {
            return json_encode($this->_jsonColumns[$name]['value']);
        }

        if (array_key_exists($name, $this->_recordColumns) && $this->_recordColumns[$name]['hasValue']) {
            $result = $this->_getIdValuesFormRecordColumn($name);
            if (array_key_exists($name, $result)) {
                return $result[$name];
            }
        }

        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }

        if (isset($this->_idValue[$name])) {
            return $this->_idValue[$name];
        }

        return null;
    }

    /**
     * Zapisuje rekord w tabeli wykonując insert lub update w zależności, czy istnieje id rekordu.
     * 
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
     * Usuwa rekord z tabeli.
     * 
     * @return boolean informacja o powodzeniu
     */
    public function remove() {
        if (null === $this->_idValue) {
            return false;
        }

        self::$db->delete($this->_tableName, $this->_getWhere());
        $this->_exists = false;
        return true;
    }

    /**
     * Usuwa grupę rekordów pasujących do zapytania where.
     * 
     * @param array tablica warunków WHERE do zapytania do bazy danych
     * @return int liczba usuniętych rekordów
     */
    public static function delete($where) {
        $obj = new static();

        if (empty($where)) {
            return 0;
        }

        return self::$db->delete($obj->_tableName, $where);
    }

    /**
     * Pobiera parametr where dla zapytań wybierających, usuwających i aktualizujących z wykorzystaniem podanego id lub zdefiniowanego w obiekcie.
     * 
     * @param int $id identyfikator rekordu
     * @return array|string część WHERE zapytania SQL
     */
    final protected function _getWhere($id = null) {
        if (null === $id) {
            $id = $this->getFullId();
        }

//        $id = $this->_validateIdentifier($id);

        $where = [];
        foreach ($this->_idColumns as $col) {
            if (!isset($id[$col])) {
                $where[] = self::$db->quoteIdentifier($col) . ' is null';
            } else {
                $where[self::$db->quoteIdentifier($col) . ' = ?'] = $id[$col];
            }
        }
        return $where;
    }

    /**
     * Zwraca obiekt zapytania select z przygotowanymi kolumnami i nazwą tabeli.
     * 
     * @return \Zend_Db_Select
     */
    protected function _getSelect() {
        $select = self::$db->select()
                ->from($this->_tableName, $this->getColumns());
        return $select;
    }

    /**
     * Zwraca obiekt zapytania select z przygotowanymi kolumnami i nazwą tabeli.
     * Dodaje do zapytania wszystkie warunki where z metody _getWhere().
     * 
     * @param int $id identyfikator rekordu
     * @param mixed $where Parametry zapytania, jeżeli brak pobierane na podstawie klucza głównego
     * @return \Zend_Db_Select
     */
    final protected function _getSelectWhere($id = null, $where = null) {
        $select = $this->_getSelect();

        if (null === $where && null !== $id) {
            $where = $this->_getWhere($id);
        }

        self::_addWhereToSelect($select, $where);

        return $select;
    }

    /**
     * Zwraca obiekt zapytania select z przygotowanymi kolumnami i nazwą tabeli.
     * 
     * @return \Zend_Db_Select
     */
    public static function getSelect() {
        $obj = new static();
        return $obj->_getSelect();
    }

    /**
     * Zwraca obiekt reprezentujący rekord o podanym identyfikatorze.
     * 
     * @param int|string|array $id
     * @return record
     */
    public static function get($id) {
        $obj = new static();
        try {
            $id = $obj->_validateIdentifier($id);
        } catch (\Skinny\Db\Record\RecordException $ex) {
            return null;
        }

        if ($obj->_load($id)) {
            return $obj;
        } else {
            return null;
        }
    }

    /**
     * Dodaje do obiektu elementy znajdujące się w tablicy asocjacyjnej.
     * 
     * @param array $data
     * @return boolean
     */
    public function importData(array $data, $useFiltering = false) {
        if (empty($data)) {
            return true;
        }

        $this->_setData($data, $useFiltering);

        return true;
    }

    /**
     * Ładuje dane rekordu do obiektu używając podanego id.
     * 
     * @param mixed $id identyfikator rekordu (lub tablica asocjacyjna "kolumna => identyfikator" gdy primary key jest wielopolowy)
     * @return boolean informacja o powodzeniu
     */
    protected function _load($id) {
        $this->_exists = false;

        $select = $this->_getSelectWhere($id);
        $data = self::$db->fetchRow($select);

        if ($data) {
            // ustawiamy dane
            $this->_idValue = $id;

            foreach ($this->_readingDisabledColumns as $column) {
                unset($data[$column]);
            }

            $this->_setData($data);

            $this->_exists = true;
        }

        return $this->_exists;
    }

    /**
     * Przygotowuje selecta na podstawie podanych argumentów.
     * 
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return \Zend_Db_Select
     */
    public function prepareSelect($where = null, $order = null, $limit = null, $offset = null) {
        $select = $this->_getSelectWhere(null, $where);
        if (null !== $order) {
            $select->order($order);
        }

        if (null !== $limit || null !== $offset) {
            $select->limit($limit, $offset);
        }

        return $select;
    }

    /**
     * Pobiera wszystkie rekordy będące rezultatem zapytania SELECT.
     * Ważne jest, aby zapytanie wybierało faktycznie rekordy tyczące się tego obiektu oraz wszelkie dodatkowe użyte kolumny były zawarte w _disallowedColumns.
     * W przeciwnym wypadku funckje zapisujące rekord się nie powiodą.
     * UWAGA! To sprawa programisty, czy select wybiera właściwe kolumny z właściwych tabel. Nie ma co do tego żadnej walidacji!
     * 
     * @param string|Zend_Db_Select $select zapytanie SELECT do bazy
     * @return RecordCollection kolekcja obiektów rekordów będących rezultatem zapytania
     * @todo Sprawdzenie typu $collectionType
     */
    public static function select($select, $collectionType = null) {
        $records = static::_select($select);

        if (null === $collectionType) {
            $collection = new RecordCollection($records);
        } else {
            $collection = new $collectionType($records);
        }

        return $collection;
    }

    /**
     * Pobiera wszystkie rekordy będące rezultatem zapytania SELECT.
     * Ważne jest, aby zapytanie wybierało faktycznie rekordy tyczące się tego obiektu oraz wszelkie dodatkowe użyte kolumny były zawarte w _disallowedColumns.
     * W przeciwnym wypadku funckje zapisujące rekord się nie powiodą.
     * UWAGA! To sprawa programisty, czy select wybiera właściwe kolumny z właściwych tabel. Nie ma co do tego żadnej walidacji!
     * 
     * @param string|Zend_Db_Select $select zapytanie SELECT do bazy
     * @return array tablica obiektów rekordów będących rezultatem zapytania
     */
    public static function selectArray($select) {
        return static::_select($select);
    }

    /**
     * Pobiera tablicę wszystkich rekordów spełniających podane warunki.
     * 
     * @param array $join warunki złączenia JOIN
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return array tablica rekordów będących rezultatem zapytania
     */
    protected static function _find(array $join, $where, $order, $limit, $offset) {
        $obj = new static();

        // select
        $select = $obj->prepareSelect($where, $order, $limit, $offset);

        if (!empty($join)) {
            foreach ($join as $value) {
                if (!is_array($value)) {
                    throw new RecordException('Invalid join format');
                }

                // typ złączenia
                $joinType = 'join';
                if (isset($value[0]) && is_string($value[0])) {
                    switch ($value[0]) {
                        case 'join':
                        case 'joinLeft':
                        case 'joinRight':
                            $joinType = $value[0];
                            break;
                    }
                }

                // tabela łączona
                if (!isset($value[1]) || !is_string($value[1]) && !is_array($value[1])) {
                    throw new RecordException('Invalid joined table');
                }

                $table = $value[1];

                // warunek złączonia
                if (!isset($value[2]) || !is_string($value[2])) {
                    throw new RecordException('Invalid join ON clause');
                }

                $joinOn = $value[2];

                // kolumny dołączane
                $cols = '';
                if (isset($value[3]) && (is_string($value[3]) || is_array($value[3]))) {
                    $cols = $value[3];
                }

                $select->$joinType($table, $joinOn, $cols);
            }
        }

        return static::_select($select);
    }

    /**
     * Pobiera wszystkie rekordy spełniające podane warunki.
     * 
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return array tablica obiektów rekordów będących rezultatem zapytania
     */
    public static function findArray($where = null, $order = null, $limit = null, $offset = null) {
        return static::_find([], $where, $order, $limit, $offset);
    }

    /**
     * Pobiera kolekcję wszystkich rekordów spełniających podane warunki.
     * 
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return RecordCollection kolekcja rekordów będących rezultatem zapytania
     */
    public static function find($where = null, $order = null, $limit = null, $offset = null) {
        $records = static::_find([], $where, $order, $limit, $offset);

        $collection = new RecordCollection($records);

        return $collection;
    }

    /**
     * Pobiera wszystkie rekordy spełniające podane warunki z uwzględnieniem złączeń.
     * Złączenia $join są tablicą tablic zawierających definicje złączeń.
     * Wewnętrzna tablica powinna zawierać 3 lub 4 elementy (ostatni jest opcjonalny):
     * - typ złączenia: "join", "joinLeft"
     * - tablicę łączoną: analogicznie jak przy złączeniu w Zend_Db_Select
     * - warunek złączenia: analogicznie jak przy złączeniu w Zend_Db_Select
     * - dołączone kolumny: analogicznie jak przy złączeniu w Zend_Db_Select, z tym, że, gdy nie podano, nie dołącza żadnych
     * 
     * @param array $join warunki złączenia JOIN
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return array tablica obiektów rekordów będących rezultatem zapytania
     */
    public static function findArrayJoin(array $join, $where = null, $order = null, $limit = null, $offset = null) {
        return static::_find($join, $where, $order, $limit, $offset);
    }

    /**
     * Pobiera wszystkie rekordy spełniające podane warunki z uwzględnieniem złączeń.
     * Złączenia $join są tablicą tablic zawierających definicje złączeń.
     * Wewnętrzna tablica powinna zawierać 3 lub 4 elementy (ostatni jest opcjonalny):
     * - typ złączenia: "join", "joinLeft"
     * - tablicę łączoną: analogicznie jak przy złączeniu w Zend_Db_Select
     * - warunek złączenia: analogicznie jak przy złączeniu w Zend_Db_Select
     * - dołączone kolumny: analogicznie jak przy złączeniu w Zend_Db_Select, z tym, że, gdy nie podano, nie dołącza żadnych
     * 
     * @param array $join warunki złączenia JOIN
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @param int $limit część zapytania LIMIT
     * @param int $offset część zapytania OFFSET
     * @return RecordCollection kolekcja obiektów rekordów będących rezultatem zapytania
     */
    public static function findJoin(array $join, $where = null, $order = null, $limit = null, $offset = null) {
        $records = static::_find($join, $where, $order, $limit, $offset);

        $collection = new RecordCollection($records);

        return $collection;
    }

    /**
     * Pobiera pierwszy rekord spełniający podane warunki.
     * 
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @return static pierwszy rekord spełniający warunki lub null
     */
    public static function findOne($where = null, $order = null, $offset = null) {
        $result = static::findArray($where, $order, 1, $offset);
        if (!empty($result)) {
            return $result[0];
        }

        return null;
    }

    /**
     * Pobiera pierwszy rekord spełniający podane warunki.
     * 
     * @param string $where część zapytania WHERE
     * @param string $order część zapytania ORDER BY
     * @return static pierwszy rekord spełniający warunki lub null
     */
    public static function findOneJoin(array $join, $where = null, $order = null, $offset = null) {
        $result = static::findArrayJoin($join, $where, $order, 1, $offset);
        if (!empty($result)) {
            return $result[0];
        }

        return null;
    }

    /**
     * Zwraca liczbę rekordów spełniających podane warunki.
     * 
     * @param type $where część zapytania WHERE
     * @return integer
     */
    public static function count($where = null) {
        $select = static::$db->select()->from(['t' => static::getTableName()], ['COUNT(1)']);

        self::_addWhereToSelect($select, $where);

        return self::$db->fetchOne($select);
    }

    /**
     * Dodaje do zapytania SELECT podane warunki WHERE.
     * 
     * @param \Zend_Db_Select $select zapytanie do modyfikacji
     * @param string|array $where warunki WHERE zapytania
     */
    protected static function _addWhereToSelect(\Zend_Db_Select $select, $where) {
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
    }

    /**
     * Konwertuje wyniki z zapytania do bazy na tablicę rekordów.
     * 
     * @param type $arrayOfAssocArrays Tablica tablic asocjacyjnych
     * @return array
     * @deprecated since version 0.3 na rzecz RecordCollection
     */
    public static function toRecords(array $arrayOfAssocArrays) {
        $array = [];
        foreach ($arrayOfAssocArrays as $assocArray) {
            $array[] = static::toRecord($assocArray);
        }

        return $array;
    }

    /**
     * Konwertuje tablicę asocjacyjną (np. wiersz z bazy danych) na obiekt record.
     * 
     * @param array $assocArray
     * @return \static
     * @deprecated since version 0.3 na rzecz metody importData()
     * @see importData()
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
     * 
     * @param string $col nazwa kolumny
     * @param array $records Tablica rekordów
     * @return array Tablica wartości z wybranej kolumny
     * @deprecated since version 0.3 na rzecz RecordCollection::__get()
     */
    public static function fetchCol($col, array $records) {
        if (!$col) {
            throw new RecordException('No column specified');
        }

        $array = [];
        if (empty($records)) {
            return $array;
        }

        foreach ($records as $record) {
            if (!($record instanceof self)) {
                continue;
            }

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

        return $array;
    }

    /**
     * Walidacja identyfikatora
     * Jeżeli identyfikator nie jest tablicą i przejdzie poprawnie walidację, zostaje zwrócony w formie tablicy.
     * 
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
                    throw new RecordException("Incomplete identifier for multi-column primary key. Key part '$column' is not set.");
                }
            }
        }
        return $id;
    }

    /**
     * Sprawdza czy identyfikator jest prawidłowy.
     * 
     * @param mixed $id
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

    /**
     * Pobiera ostatnio wstawioną wartość automatycznego id z tabeli głównej rekordu.
     * Można podać, o którą kolumnę id chodzi, jeśli jest to niejednoznaczne.
     * 
     * @param string $idCol nazwa kolumny, któej wartość chcemy pobrać
     * @return string
     */
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

    /**
     * Pobiera z bazy danych ostatnio wstawioną wartość identyfikatora z podanej kolumny.
     * 
     * @param string $idCol
     * @return string
     */
    protected function getLastInsertIdHelper($idCol/* , $tableName = null */) {
        /* if (null === $tableName) {
          $tableName = $this->_tableName;
          } */

        return self::$db->lastInsertId(/* $tableName */$this->_tableName, $idCol);
    }

    /**
     * Stwierdza, czy podany w argumencie rekord jest dokładnie tym samym co bieżący.
     * 
     * @param \Skinny\Db\Record\RecordBase $record
     */
    public function equals(RecordBase $record) {
        if (null === $record) {
            return false;
        }

        if (!$this->exists() || !$record->exists()) {
            return false;
        }

        return
                $this->_tableName == $record->_tableName &&
                $this->getFullId() == $record->getFullId();
    }

    /**
     * Metoda używana do serializacji obiektu do JSONa
     * @return array
     */
    public function jsonSerialize() {
        return $this->_data;
    }

    /**
     * Metoda używana przy isset($this[$offset])
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset) {
        return
                key_exists($offset, $this->_data) ||
                key_exists($offset, $this->_idValue) ||
                key_exists($offset, $this->_collectionColumns) ||
                key_exists($offset, $this->_jsonColumns) ||
                key_exists($offset, $this->_recordColumns)
        ;
    }

    /**
     * Metoda używana przy return $this[$offset]
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->$offset;
    }

    /**
     * Metoda używana przy $this[$offset] = $value
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        $this->$offset = $value;
    }

    /**
     * Metoda używana przy unset($this[$offset])
     * @param mixed $offset
     * @return boolean
     */
    public function offsetUnset($offset) {
        unset($this->$offset);
    }

}
