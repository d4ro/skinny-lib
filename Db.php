<?php

namespace Skinny;

use Skinny\Db\Table;
use Skinny\Db\Sql;

/**
 * Reprezentacja połącenia z bazą danych.Skinny\Db jest nakładką na PDO umożliwiającą łatwiejszą i czytelniejszą obsługę bazy przez programistę.
 * Założeniem jest praca na jednej bazie w ramach jednego połączenia.
 *
 * @author Daro
 */
class Db extends \PDO {

    /**
     * Konstruktor instancji połączenia z bazą danych.
     * @param string $dsn string połączeniowy PDO zawierający przede wszystkich nazwę adaptera bazy danych i jego podstawowe opcje,
     * jak ścieżka do pliku bazy lub adres jej serwera
     * @param string $user nazwa użytkownika połączenia z bazą danych
     * @param string $pass hasło użytkownika
     * @param array $driver_options dodatkowe opcje adaptera bazy danych
     */
    public function __construct($dsn, $user = null, $pass = null, $driver_options = array()) {
        parent::__construct($dsn, $user, $pass, $driver_options);
        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\Skinny\Db\Statement', array($this)));
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Prechwytuje nazwę tabeli i zwraca obiekt Skinny\Db\Table reprezentujący tabelę o podanej nazwie
     * @param string $name
     * @return \Skinny\Db\Table obiekt reprezentujący tabelę o podanej nazwie
     */
    public function __get($name) {
        return new Table($this, $name);
    }

    /**
     * Zwraca nowy obiekt Skinny\Db\Sql w celu utworzenia zapytania SQL w kontekście danego połączenia.
     * @param string $method
     * @param string $table
     * @return \Skinny\Db\Sql
     */
    public function sql($method = null, $table = null) {
        return new Sql($this, $method, $table);
    }

    public function prepare($statement, array $driver_options = array()) {
        if (is_object($statement))
            $statement = $statement->_toString();
        return parent::prepare($statement, $driver_options);
    }

}
