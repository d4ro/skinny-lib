<?php

namespace Skinny\Db;

use Skinny\Db;

/**
 * Description of Sql
 *
 * @author Daro
 */
class Sql extends Bindable {

    /**
     * Metoda użyta do zapytania SQL
     * Dozwolone wartości (case-insensitive):
     * - SELECT
     * - UPDATE
     * - INSERT
     * - REPLACE
     * - DELETE
     * - TRUNCATE
     * Propozycje:
     * - SET
     * - CALL
     * - DROP (table)
     * - ALTER (table)
     * - CREATE (table)
     * - RENAME
     * @var string
     */
    protected $_method;     // przyjmuje pojedyncza wartosc
    protected $_table;      // tabela glowna operacyjna - pojedyncza wartosc
//    protected $_columns;    // przechowuje kolumny do zapytania np. from klucze dla update, insert (wartosci znajduja sie w $_data)
    // to zostało zmienione! teraz kolumny są kluczami w $_data
    protected $_data;       // dejta do ewrytink
    protected $_insertdata;
    protected $_where;
    protected $_having;
    protected $_groupby;
    protected $_orderby;
    protected $_limit;

    public function __construct(Db $db, $method = null, $table = null) {
        $this->_db    = $db;
        $this->setMethod($method);
        $this->_table = $table;
    }

    protected function _assemble() {
        // TODO: skonstruowanie całego zapytania do $_expression bez bindowania parametrów z $_values ale z bindowaniem wszystkich komponentów, które są Bindable
        return parent::_assemble();
    }

    public function __set($name, $value) {
        $this->_data[$name] = $value;
    }

    public function __get($name) {
        // Daro: o co mi chodziło w tej funkcji?
        // TODO: walidacja
        return $this->_data[$name];
    }

    public function setMethod($method) {
        // TODO: walidacja czy już nie została ustawiona
        switch (strtoupper($method)) {
            case 'SELECT':      // where nieobowiązkowy
            case 'INSERT':      // where zbędny/zabroniony
            case 'REPLACE':     // j.w.
            case 'UPDATE':      // where obowiązkowy
            case 'ORINSERT':    // j.w.
            case 'DELETE':      // j.w.
            case 'TRUNCATE':    // where zbędny/zabroniony
                $this->_method = $method;
                break;

            default:
                throw new \UnexpectedValueException('Method "' . $method . '" is not supported by the SQL builder.');
        }
        return $this;
    }

    public function setColumns($columns = null) {
        $this->_columns = (array) $columns;
        return $this;
    }

    public function addColumns($columns) {
        // TODO: będzie to się tyczyło tylko selecta
        // dodaje kolumnę lub kolumny do listy pobieranych z bazy kolumn
        $this->_columns = array_merge((array) $this->_columns, (array) $columns);
        return $this;
    }

    public function setData($data = null) {
        $this->_data = (array) $data;
        return $this;
    }

    public function setInsertData($data) {
        $this->_insertdata = (array) $data;
        return $this;
    }

    public function select($columns = null) {
        return $this
                ->setMethod('SELECT')
                ->setColumns($columns);
    }

    public function update($data = null) {
        return $this
                ->setMethod('UPDATE')
                ->setData($data);
    }

    public function insert($data = null) {
        return $this
                ->setMethod('INSERT')
                ->setInsertData($data);
    }

    public function delete() {
        return $this
                ->setMethod('DELETE');
    }

    public function orInsert($data = null) {
        return $this
                ->setMethod('ORINSERT')
                ->setInsertData($data);
    }

    public function reset($part) {
        switch (strtolower($part)) {
            case 'where':
                $this->_where   = null;
                break;
            case 'order':
            case 'orderby':
                $this->_orderby = null;
                break;
            case 'group':
            case 'groupby':
                $this->_groupby = null;
                break;
            default:
                throw new \UnexpectedValueException('There is no such part as "' . $part . '".');
        }
        return $this;
    }

    public function where($where) {
        // $where może być stringiem, arrayem lub obiektem Where
        // niedozwolone jest użycie where('id = ?', $id); zamiast tego należy użyć where(array('id = ?' => $id));
        // restrykcja ta jest spowodowana niespójnością implementacji where'a w Zendzie, która prowadzi do błedów logicznych
        // where może zostać ustawiony tylko raz
    }

    public function order($orderby) {
        // order może zostać ustawiony tylko raz
        $this->_orderby = $orderby;
        return $this;
    }

    public function group($groupby) {
        // group może zostać ustawiony tylko raz
        $this->_groupby = $groupby;
        return $this;
    }

    public function table($table) {
        // ustawia tabelę (np. główna tabela do SELECT FROM)
        $this->_table = $table;
        return $this;
    }

}
