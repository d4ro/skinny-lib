<?php

namespace Skinny\Db;

/**
 * Description of Where
 * Where'y nie są świadome ani sql ani db, nie potrzebują tego. Dopiero przy przerabianiu go na string podaje się db.
 *
 * @author Daro
 */
class Where extends Bindable {

    /**
     * Wyrażenie WHERE w postaci stringu z ewentualnymi nazwanymi (?) lub nie (:param) parametrami.
     * @var string
     */
    protected $_expression;

    /**
     * Tablica wartości dla parametrów wyrażenia WHERE.
     * @var array
     */
    protected $_values;

    /**
     * Tworzy obiekt Where na podstawie podanego wyrażenia i ewentualnych parametrów.
     * Możeliwe użycia:
     * new Where('col=9');
     * new Where('col=?', 9);
     * new Where(array('col=?' => 9));
     * new Where('col=:val', array('val' => 9))
     * new Where('col between ? and ?', 9) // co nie ma dużego sensu, ale jest dozwolone - chyba powinno być... a może... nie...?
     * new Where('col between ? and ?', 8, 9);
     * new Where('col between ? and ?', array(8, 9));
     * new Where('col between :val1 and :val2', array('val1' => 8, 'val2' => 9));
     * new Where('col1 = : val1 and col2 between :val1 and :val2', array('val1' => 8, 'val2' => 9));
     * @param string|array $expression
     * @param array $values
     */
    public function __construct($expression, array $values = array()) {
        $args              = func_get_args();
        $this->_expression = array_shift($args);

        if (count($args) == 1)
            $this->_values = $values;
        else
            $this->_values = $args;

        if (is_array($expression)) {
            if (count($expression) !== 1)
                throw new \InvalidArgumentException('Invalid expression: expected string or single element array.');
            $this->_expression = key($expression);
            array_unshift($this->_values, $expression[0]);
        }
    }

    public function bind($params, $value = null) {
        // TODO: binduje parametry w segmentach i wewnątrz nich (rekurencja)
    }

    protected function _assemble() {
        // TODO: generowanie stringu WHERE na podstawie segmentów i typu ich złączenia
    }

    public static function simple($expression, $params = null) {
        // prosty where jednostringowy lub z parametrami jako array
    }

    public static function is($expression, $sign, $value) {
        // sign: <, >, =, <=, >=, <>, !=, IN, LIKE, ILIKE
    }

    public static function eq($expression, $value) {
        return self::is($expression, '=', $value);
    }

    public static function equals($expression, $value) {
        return self::eq($expression, $value);
    }

    public static function gt($expression, $value) {
        return self::is($expression, '>', $value);
    }

    public static function graterThan($expression, $value) {
        return self::gt($expression, $value);
    }

    public static function lt($expression, $value) {
        return self::is($expression, '<', $value);
    }

    public static function lowerThan($expression, $value) {
        return self::lt($expression, $value);
    }

    public static function ge($expression, $value) {
        return self::is($expression, '>=', $value);
    }

    public static function greaterEquals($expression, $value) {
        return self::ge($expression, $value);
    }

    public static function le($expression, $value) {
        return self::is($expression, '<=', $value);
    }

    public static function lowerEquals($expression, $value) {
        return self::le($expression, $value);
    }

    public static function in($expression, $values) {
        // id IN (1,2,3)
        // id IN (SELECT id from tab)
        // TODO: quotowanie - zamiana wartości na nazwane paramsy
        return self::is($expression, 'IN', implode(',', (array) $values));
    }

    public static function like($expression, $value) {
        return self::is($expression, 'LIKE', $value);
    }

    public static function between($expression, $values) {
        // TODO: quotowanie - zamiana wartości na nazwane paramsy
        return self::is($expression, 'BETWEEN', implode(' AND ', (array) $values));
    }

    public static function orWhere($where1 = null, $where2 = null, $wheren = null) {
        $wheres = func_get_args();
        // TODO: połączenie orem i zwrot
    }

    public static function andWhere($where1 = null, $where2 = null, $wheren = null) {
        $wheres = func_get_args();
        // TODO: połączenie anddem i zwrot
    }

}
