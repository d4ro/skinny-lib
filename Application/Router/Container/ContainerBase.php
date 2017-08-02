<?php

namespace Skinny\Application\Router\Container;

/**
 * Klasa bazowa kontenera na wyliczone części składowe zapytania do aplikacji.
 *
 * @author Daro
 */
abstract class ContainerBase implements ContainerInterface {

    /**
     * Base URL path of request
     * @var string
     */
    protected $_baseUrl;

    /**
     * URL path of request
     * @var string
     */
    protected $_requestUrl;

    /**
     * Action object
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
        $this->_action      = null;
        $this->_actionUrl   = '';
        $this->_actionParts = array();
        $this->_actionDepth = 0;
        $this->_actionMatch = false;
        $this->_args        = array();
        $this->_argCount    = 0;
        $this->_params      = array();
        $this->_paramCount  = 0;
    }

    public function getBaseUrl() {
        return $this->_baseUrl;
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

    /**
     * Returns depth level of the action counting from base.
     * 
     * @return int
     */
    public function getActionDepth() {
        return $this->_actionDepth;
    }

    /**
     * Returns whether action in the container is the same as in the original request.
     * [Obsolete cadidate]
     * 
     * @return bool
     */
    public function getActionMatch() {
        return $this->_actionMatch;
    }

    /**
     * Returns action parts subset of whole arguments array.
     * 
     * @return array
     */
    public function getActionParts() {
        return $this->_actionParts;
    }

    /**
     * Returns arguments array extracted from the request.
     * 
     * @return array
     */
    public function getArgs() {
        return $this->_args;
    }

    /**
     * Returns number of arguments.
     * 
     * @return int
     */
    public function getArgCount() {
        return $this->_argCount;
    }

    /**
     * Returns associated array of parameters where names are keys.
     * 
     * @return array
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * Returns parameters in string format.
     * 
     * @return string
     * @todo obsłużyć parametry tablicowe
     */
    public function getParamsString() {
        $result = '';
        foreach ($this->_params as $key => $value) {
            $result .= $key . '/' . $value . '/';
        }
        return $result;
    }

    /**
     * Returns number of parameters.
     * 
     * @return int
     */
    public function getParamCount() {
        return $this->_paramCount;
    }

    public function setBaseUrl($baseUrl) {
        $this->_baseUrl = $baseUrl;
    }

    /**
     * Sets request URL in the constainer.
     * @param string $requestUrl
     */
    public function setRequestUrl($requestUrl) {
        $this->_requestUrl = $requestUrl;
    }

    /**
     * Sets action object in the container.
     * @param \Skinny\Action $action
     */
    public function setAction($action) {
        $this->_action = $action;
    }

    /**
     * Sets action parts and constructs action url.
     * @param array $actionParts
     */
    public function setActionParts(array $actionParts) {
        $this->_actionParts = $actionParts;
        $this->_actionUrl   = implode('/', $actionParts);
        $this->_actionDepth = count($actionParts);
    }

    public function setActionMatch($actionMatch) {
        $this->_actionMatch = (bool) $actionMatch;
    }

    public function resetArgs(array $args) {
        $this->_args     = $args;
        $this->_argCount = count($args);
    }

    public function setParams(array $params) {
        $this->_params     = array_merge($this->_params, $params);
        $this->_paramCount = count($this->_params);
    }

    public function resetParams(array $params = array()) {
        $this->_params     = $params;
        $this->_paramCount = count($this->_params);
    }

}
