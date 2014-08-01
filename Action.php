<?php

namespace Skinny;

/**
 * Description of Action
 *
 * @author Daro
 */
abstract class Action {

    /**
     * 
     * @var Application
     */
    private $_application;
    private $_usage;

    /**
     * Konstruktor akcji - nie przeciążamy! Od tego jest _init().
     */
    final public function __construct() {
        $this->_usage = new Action\Usage();
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
     * Zwraca informację, czy jest zezwolenie na wykonanie akcji (return true) lub nie.
     * Należy w tym miejscu sprecyzować sposoby (ways) wykorzystania akcji przez użytkownika (przy pomocy $this->getUsage()->allowUsage()).
     * @return boolean czy jest zezwolenie na wykonanie danej akcji
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

    final public function setApplication(Application $application) {
        $this->_application = $application;
    }

    /* uzytkowe */

    /**
     * Pobiera konfigurację aplikacji
     * @param string $key
     * @return mixed
     */
    public function getConfig($key = null) {
        return $this->_application->getConfig($key);
    }

    /**
     * Pobiera ilość argumentów żądania
     * @return integer
     */
    public function getArgCount() {
        return $this->getRequest()->current()->getArgCount();
    }

    /**
     * Pobiera argument żądania o podanym indeksie z wartością domyślną, gdy nie istnieje.
     * @param integer $index indeks argumentu
     * @param mixed $default wartość domyślna zwracana, gdy argument o podanym indeksie nie istnieje
     * @return mixed wartość argumentu
     */
    public function getArg($index, $default = null) {
        return $this->getRequest()->current()->getArg($index, $default);
    }

    /**
     * Pobiera tablicę wszystkich argumentów żądania.
     * @return array
     */
    public function getArgs() {
        return $this->getRequest()->current()->getArgs();
    }

    /**
     * Pobiera parametr żądania z wartością domyślną, gdy nie został zainicjowany.
     * @param string $name nazwa parametru
     * @param mixed $default wartość domyślna zwrócona, gdy parametr nie został zainicjowany
     * @return mixed wartość parametru
     */
    public function getParam($name, $default = null) {
        return $this->getRequest()->current()->getParam($name, $default);
    }
    
    /**
     * Ustawia parametry dla bieżącego żądania
     * @param array $params tablica parametrów do ustawienia
     */
    public function setParams(array $params) {
        $this->getRequest()->current()->setParams($params);
    }

    /**
     * Sprawdza czy parametr żądania istnieje
     * @param string $name
     * @return boolean
     */
    public function hasParam($name) {
        return $this->getRequest()->current()->hasParam($name);
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
    public function getActionUrl() {
        return $this->getRequest()->current()->getActionUrl();
    }

    /**
     * Pobiera instancję zapytania do aplikacji.
     * @return Request
     */
    public function getRequest() {
        if (null === $this->_application)
            return null;

        return $this->_application->getRequest();
    }

    /**
     * Pobiera instancję odpowiedzi aplikacji.
     * @return Request
     */
    public function getResponse() {
        if (null === $this->_application)
            return null;

        return $this->_application->getResponse();
    }

    /**
     * Pobiera obiekt komponentu z aplikacji.
     * @param string $name
     * @return mixed
     */
    public function getComponent($name) {
        if (null === $this->_application)
            return null;

        return $this->_application->getComponent($name);
    }

    /**
     * Nieistniejąca właściwość - pobranie komponentu aplikacji
     * np. $this->view->... odwołuje się do komponentu "view".
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->getComponent($name);
    }

    /**
     * Pobiera ścieżkę bazową aplikacji.
     * @return string
     */
    public function getBaseUrl() {
        return $this->getRequest()->getRouter()->getBaseUrl();
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
        $this->getRequest()->forceNext(new Request\Step($request_url, $params));
        throw new Action\ForwardException;
    }

    /**
     * Przekierowuje na podany URL z podanymi parametrami.
     * Jeżeli URL nie zostanie podany (null) zostanie użyty bieżący.
     * Przedostatnim parametrem można wymusić przekierowanie na HTTPS (true) lub HTTP (false),
     * a ostatnim kod HTTP przekierowania.
     * @param string $url
     * @param array $params
     * @param boolean $secure
     * @param integer $returnCode
     */
    public function redirect($url = null, array $params = array(), $secure = null, $returnCode = 302) {
        if (!Url::isAbsolute($url))
            $url = Url::combine($this->getBaseUrl(), $url);
        if (null === $secure)
            Location::redirect($url, $params, $returnCode);
        elseif ($secure)
            Location::redirectHttps($url, $params, $returnCode);
        else
            Location::redirectHttp($url, $params, $returnCode);
    }

    /**
     * Użycie w akcji informuje aplikację, że akcja nie istnieje i powinna wyświetlić się strona not found.
     */
    public function noAction() {
        $notFoundAction = $this->_application->getConfig()->actions->notFound(null);
        if (null !== $notFoundAction)
            $this->forward($notFoundAction, ['error' => 'notFound', 'step' => $this->getRequest()->current()]);
        // TODO: 404
    }
    
    /**
     * Forwarduje aplikację do akcji błędu z przekazaniem wybranych parametrów.
     * 
     * @param array $params Tablica parametrów
     * @param string $type Typ błędu
     */
    public function error(array $params, $type = 'other') {
        $errorAction = $this->_application->getConfig()->actions->error(null);
        if (null !== $errorAction)
            $this->forward($errorAction, array_merge(['error' => $type, 'step' => $this->getRequest()->current()], $params));
    }

}
