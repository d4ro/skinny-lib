<?php

namespace Skinny\Db;

use Skinny\Db;

/**
 * Description of Ext
 *
 * @author Daro
 */
class Ext extends Db {

    /**
     * Wstawia rekordy $data do tabeli $table
     * @param string $table nazwa tabeli
     * @param mixed $data dane rekordów w postaci:
     * - stringu z wartościami - wartości wszystkich kolumn po przecinku lub 'SET ...',
     * - stringu z zapytaniem SELECT lub obiektem Skinny\Db\Sql,
     * - talicy, gdzie klucze są nazwami kolumn, a wartości ich wartościami;
     * dozwolona jest dwuwymiarowa tablica rekordów w celu dodania większej ilości danych na raz
     * @param mixed $columns jawnie określone kolumny, których wartości zostaną wstawione;
     * działa jedynie, gdy $data jest zbiorem wartości wylistowanych po przecinku, indeksowanym arrayem lub zapytaniem SELECT
     * @return \Skinny\Db\Statement odpowiedź SQL
     */
    public function insert($table, $data, $columns = null) {
        
    }

    public function replace($table, $data) {
        
    }

    public function delete($table, $where) {
        // jeżeli $where jest pusty należy wywołać exception z komunikatem, że where jest pusty, a jeżeli programista chce wyczyścić tabelę, niech użyje truncate()
    }

    public function update($table, array $data, $where) {
        
    }

    /**
     * Aktualizuje rekordy podanymi danymi wtedy i tylko wtedy, gdy:
     * 1. warunek nie jest pusty (warunki typu 1=1 nie są puste, mimo, iż rezultat ich działania jest podobny do braku warunku)
     * 2. warunek nie ogranicza ilości modyfikowanych rekordów do zera.
     * W przeciwnym wypadku funkcja wstawia nowe dane.
     * @param string $table nazwa tabeli bazy danych
     * @param array $data dane aktualizacji oraz wstawiania, gdy $insertData === null
     * @param mixed $where warunek aktualizacji danych
     * @param mixed $insertData dane wstawiania
     */
    public function updateOrInsert($table, array $data, $where, $insertData = null) {
        // $insertData może być:
        // 1. obiektem Sql z zapytaniem SELECT,
        // 2. stringiem,
        // 3. arrayem danych (jeden rekord), gdzie klucz jest nazwą kolumny,
        // 4. tablicą arrayów danych (wiele rekordów).
        // 
        // $where może być:
        // 1. obiektem Where,
        // 2. stringiem,
        // 3. arrayem (kolejne elementy są łączone słowem kluczowym 'AND'), gdzie:
        //  - klucz jest kolejnym indeksem - wartość może być:
        //      {rekurencja} (czyli obiektem Where, stringiem, itd.)
        //  - klucz ma wartość (case insensitive) 'or' lub zaczyna się od 'or_', a wartość jest arrayem:
        //      {rekurencja pktu 3.}, przy czym kolejne elementy są łączone słowem kluczowym 'OR'
        //  - klucz ma wartość (case insensitive), 'and' lub zaczyna się od 'and_', a wartość jest arrayem:
        //      {rekurencja pktu 3.}
        //  - klucz jest tekstowy i zawiera parametry nazwane i/lub nienazwane - wartość może być:
        //      1. cokolwiek poza arrayem - uzupełnia wszystkie nienazwane parametry podaną wartością
        //      2. array - wartości paramtrów nazwanych i nieznwanych - patrz komentarz w execute()
        // ! wszystkie wartości poza arrayem są ewaluowane do stringa (w tym także obiekty typu Where)
        //
        // $data może być:
        // 1. arrayem danych (jeden rekord), gdzie klucz jest nazwą kolumny.
    }

    public function fetchOne($sql, array $params = null) {
        // wykonuje podanego sqla z parametrami i zwraca pojedyńczą wartość (pierwszą kolumnę z pierwszego wiersza)
    }

    public function fetchRow($sql, array $params = null) {
        // wykonuje podanego sqla z parametrami i zwraca pierwszy wiersz w postaci tablicy
    }

    public function fetchCol($sql, array $params = null) {
        // wykonuje podanego sqla z parametrami i zwraca pierwszą kolumnę w postaci tablicy
    }

    public function fetchPairs($sql, array $params = null) {
        // wykonuje podanego sqla z parametrami i zwraca pierwszą i drugą kolumnę w postaci tablicy, gdzie pierwsza kolumna jest kluczem, a druga wartością
    }

    public function fetchAll($sql, array $params = null) {
        // wykonuje podanego sqla z parametrami i zwraca wszystkie wartości w postaci tablicy dwuwymiarowej (kolumny indeksowane nazwami)
    }

    public function truncate($table) {
        // czyści tabelę
    }

    public function execute($sql, array $params = null) {
        // wykonuje podanego sqla z parametrami
        // wartości parametrów zapytania - mogą być:
        // 1. arrayem, gdzie wartość jest wartością, a klucz może być nazwany (np. "abc" - parametr nazwany ":abc") lub być kolejnym indexem tabeli (parametr nienazwany "?")
    }

}