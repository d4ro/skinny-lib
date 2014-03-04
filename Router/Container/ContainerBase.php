<?php

namespace Skinny\Router\Container;

/**
 * Klasa bazowa kontenera na wyliczone części składowe zapytania do aplikacji.
 *
 * @author Daro
 */
abstract class ContainerBase implements ContainerInterface {

    /**
     * Ścieżka URL żądania
     * @var string
     */
    protected $_requestUrl;

    /**
     * Obiekt akcji
     * @var \Skinny\Action
     */
    protected $_action;

    /**
     * Ścieżka żądania do akcji
     * @var string
     */
    protected $_actionUrl;

    /**
     * Części składowe ścieżki do akcji
     * @var array
     */
    protected $_actionParts;

    /**
     * Głębokość ścieżki do akcji
     * @var integer
     */
    protected $_actionDepth;

    /**
     * Czy akcja docelowa zgadza się z wywoływaną
     * @var boolean
     */
    protected $_actionMatch;

    /**
     * Argumenty zapytania
     * @var array
     */
    protected $_args;

    /**
     * Ilość argumentów zapytania
     * @var integer
     */
    protected $_argCount;

    /**
     * Parametry zapytania
     * @var array
     */
    protected $_params;

    /**
     * Ilość parametrów zapytania
     * @var integer
     */
    protected $_paramCount;

    /**
     * Konstruktor inicjujący wartości domyślne.
     */
    public function __construct() {
        $this->_action = null;
        $this->_actionUrl = '';
        $this->_actionParts = array();
        $this->_actionDepth = 0;
        $this->_actionMatch = false;
        $this->_args = array();
        $this->_argCount = 0;
        $this->_params = array();
        $this->_paramCount = 0;
    }

    public function getRequestUrl() {
        return $this->_requestUrl;
    }

    public function getAction() {
        return $this->_action;
    }

    /**
     * Pobiera ścieżkę akcji
     * @return string
     */
    public function getActionUrl() {
        return $this->_actionUrl;
    }

    public function getActionDepth() {
        return $this->_actionDepth;
    }

    public function getActionMatch() {
        return $this->_actionMatch;
    }

    public function getActionParts() {
        return $this->_actionParts;
    }

    public function getArgs() {
        return $this->_args;
    }

    public function getArgCount() {
        return $this->_argCount;
    }

    public function getParams() {
        return $this->_params;
    }

    public function getParamCount() {
        return $this->_paramCount;
    }

    public function setRequestUrl($requestUrl) {
        $this->_requestUrl = $requestUrl;
    }

    public function setAction($action) {
        $this->_action = $action;
    }

    public function setActionParts(array $actionParts) {
        $this->_actionParts = $actionParts;
        $this->_actionUrl = implode('/', $actionParts);
        $this->_actionDepth = count($actionParts);
    }

    public function setActionMatch($actionMatch) {
        $this->_actionMatch = (bool) $actionMatch;
    }

    public function resetArgs(array $args) {
        $this->_args = $args;
        $this->_argCount = count($args);
    }

    public function setParams(array $params) {
        $this->_params = array_merge($this->_params, $params);
        $this->_paramCount = count($this->_params);
    }

    public function resetParams(array $params = array()) {
        $this->_params = $params;
        $this->_paramCount = count($this->_params);
    }

}
