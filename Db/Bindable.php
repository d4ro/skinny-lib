<?php

namespace Skinny\Db;

/**
 * Reprezentuje sparametryzowane wyrażenie SQL lub jego fragment.
 * Pod parametry można podstawić wartości metodą bind() lub w samym konstruktorze podając tablicę.
 *
 * @author Daro
 */
class Bindable extends Assemblable implements BindableInterface {

    /**
     * Wyrażenie
     * @var string
     */
    protected $_expression;

    /**
     * Wartości parametrów
     * @var array
     */
    protected $_values;

    /**
     * Tworzy nowy obiekt ustalając treść sparametryzowanego wyrażenia.
     * Jeżeli $expression jest tablicą, której klucz jest stringiem, uznaje klucz za wyrażenie, a wartość tablicy za wartość parametru.
     * @param mixed $expression obiekt, string lub jednoelementowy array
     * @throws InvalidArgumentException array nie jest jednoelementowy
     */
    public function __construct($expression) {
        if (is_string($expression))
            $this->_expression = $expression;
        elseif (is_object($expression))
            $this->_expression = $expression->__toString();
        elseif (is_array($expression)) {
            if (count($expression) !== 1)
                throw new \InvalidArgumentException('Invalid expression: expected string or single element array.');
            $key = key($expression);
            if (is_string($key)) {
                $this->_expression = $key;
                $this->_values[]   = $expression[$key];
            } else
                $this->_expression = $expression[$key];
        }
    }

    /**
     * Podstawia wartości pod parametry nazwane lub nienazwane.
     * @param type $params
     * @param type $value
     */
    public function bind($params, $value = null) {
        // TODO: przerobić, poprawić i naprawić
        $this->_values = array_merge((array) $this->_values, (array) $param);
    }

    protected function _assemble() {
        // przygotouje expression
        // TODO: podpisuje wszystkie parametry ich wartościami, tak jak to robi Zend
        // jeżeli jest tylko jeden nienazwany paramert, wszystkie znaki zapytania zmieniamy na jego wartość (czy ja wiem...? chyba błąd)
        // usuwa parametry, expression jest już w wersji kompletnej
    }

    public static function randomName() {
        // TODO: generowanie randomowej nazwy paramu: same litery i cyfry o stałej długości >=8
    }

}
