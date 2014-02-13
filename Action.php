<?php

namespace Skinny;

/**
 * Description of Action
 *
 * @author Daro
 */
abstract class Action {

    protected $_request;
    private $_usage;

    /**
     * Konstruktor akcji - nie przeciążamy! Od tego jest _init().
     * @param \Skinny\Request $request zapytanie HTTP
     */
    final public function __construct(Request $request) {
        $this->_request = $request;
        $this->_usage = new Action\Usage();
        $this->_init();
    }

    /**
     * [Składnik akcji - opcjonalny]
     * Inicjalizacja akcji - przedłużenie konstruktora
     * Tutaj przygotowujemy instancje obiektów, które będą potrzebne do obsługi:
     * - uprawnień (d. protection)         permission
     * - przygotowań (d. preDispatch)      prepare
     * - akcji (d. action)                 action
     * - porządkowań (d. postDispatch)     cleanup
     */
    public function _init() {
        
    }

    /**
     * [Składnik akcji - wymagany]
     * Ustala czy i na jakich zasadach użytkownik ma mieć dostęp do danej akcji.
     * Aby akcja została uruchomiona, należy sprecyzować przynajmniej jeden sposób (way)
     * wykorzystania akcji przez użytkownika (przy pomocy $this->getUsage()->allowUsage()).
     */
    abstract public function _permit();

    /**
     * [Składnik akcji - opcjonalny]
     * Przygotowania przed uruchomieniem akcji.
     * Wczytujemy (globalne?) dane, przygotowujemy je; po tym etapie mamy wszystko, co potrzebne do obsługi akcji.
     */
    public function _prepare() {
        
    }

    /**
     * [Składnik akcji - wymagany]
     * Serce akcji. Wykonywane, gdy użytkownik ma jakiekolwiek uprawnienia do akcji.
     * Dodatkowe uprawnienia można stwierdzić przy pomocy $this->getUsage()->isAllowed().
     */
    abstract public function _action();

    /**
     * [Składnik akcji - opcjonalny]
     * Zakończenie akcji, często czyszczenie danych, połączeń, pamięci, buforów, obsługa wyjścia (output).
     */
    public function _cleanup() {
        
    }

    /* krytyczne */

    /**
     * 
     * @return Action\Usage
     */
    final public function getUsage() {
        return $this->_usage;
    }

//
//    final public function setUsage($allow, $way) {
//        $this->_usage->setUsage($allow, $way);
//        return $this;
//    }
//
//    public function setUsages(array $ways) {
//        $this->_usage->setUsages($ways);
//        return $this;
//    }
//
//    final public function allowUsage($way) {
//        if (!is_array($way))
//            $way = func_get_args();
//
//        $this->_usage->allowUsage($way);
//        return $this;
//    }
//
//    public function allowUsages(array $ways) {
//        $this->_usage->allowUsages($ways);
//        return $this;
//    }
//
//    final public function disallowUsage($way) {
//        if (!is_array($way))
//            $way = func_get_args();
//
//        $this->_usage->disallowUsage($way);
//        return $this;
//    }
//
//    public function disallowUsages(array $ways) {
//        $this->_usage->disallowUsages($ways);
//        return $this;
//    }
//
//    public function isAllowed($way) {
//        return $this->_usage->isAllowed($way);
//    }

    /* uzytkowe */

    public function getArgCount() {
        return $this->getRequest()->current()->getArgCount();
    }

    public function getArg($index, $default = null) {
        return $this->getRequest()->current()->getArg($index, $default);
    }

    public function getArgs() {
        return $this->getRequest()->current()->getArgs();
    }

    public function getParam($name, $default = null) {
        return $this->getRequest()->current()->getParam($name, $default);
    }

    /**
     * Pobiera wszystkie parametry zapytania do akcji.
     * @return array
     */
    public function getParams() {
        return $this->getRequest()->current()->getParams();
    }

    /**
     * Pobiera ścieżkę żądania do aktualnej akcji.
     * @return string
     */
    public function getActionPath() {
        return $this->getRequest()->current()->getActionPath();
    }

    /**
     * Pobiera instancję zapytania do aplikacji.
     * @return Request
     */
    public function getRequest() {
        return $this->_request;
    }

    /**
     * Pobiera ścieżkę bazową aplikacji.
     * @return string
     */
    public function getBaseUrl() {
        return '/' . $this->getRequest()->getRouter()->getBaseUrl();
    }

    /**
     * Stwierdza, czy akcja została forwardowana.
     * @return boolean
     */
    public function isForwarded() {
        // TODO: czy to jest potrzebne?
        return (null !== $this->getRequest()->previous());
    }

    /**
     * Przekierowuje aktualną akcję na kolejną
     * @param string $request_url url akcji
     * @param array $params opcjonalne parametry
     */
    final protected function forward($request_url, array $params = array()) {
        $this->getRequest()->next(new Request\Step($request_url, $params));
    }

}