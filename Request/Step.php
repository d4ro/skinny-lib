<?php

namespace Skinny\Request;

use Skinny\Router;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Step
 *
 * @author Daro
 */
class Step extends Router\Container\ContainerBase {

    /**
     * Pierwszy, macierzysty krok żądania
     * @var Step
     */
    protected $_first;

    /**
     * Poprzedni krok żądania
     * @var Step
     */
    protected $_previous;

    /**
     * Następny krok żądania
     * @var Step
     */
    protected $_next;

    /**
     * Określa, czy akcja kroku żądania została przetworzona
     * @var boolean
     */
    protected $_processed;

    /**
     * Określa, czy akcja i parametry kroku żądania zostały określone
     * @var boolean
     */
    protected $_resolved;

    public function __construct($requestUrl, $params = array()) {
        $this->_requestUrl = $requestUrl;
        $this->_params = $params;
        $this->_actionMatch = true;
        $this->_resolved = false;
        $this->_processed = false;
    }

    public function next(Step $step = null) {
        if (null !== $step)
            $this->_next = $step;
        return $this->_next;
    }

    public function previous(Step $step = null) {
        if (null !== $step) {
            $this->_first = null;
            $this->_previous = $step;
        }
        return $this->_previous;
    }

    public function first() {
        if (null == $this->_first) {
            $previous = $this;
            while ($previous = $previous->previous())
                $first = $previous;
            $this->_first = $first;
        }
        return $this->_first;
    }

    public function resolve(Router\RouterInterface $router) {
        $router->getRoute($this->_requestUrl, $this);
        $this->_resolved = true;
        if (null !== $this->first() && $this->_actionPath !== $this->first()->_actionPath)
            $this->_actionMatch = false;
    }

    public function isResolved() {
        return $this->_resolved;
    }

    public function setProcessed($value) {
        $this->_processed = (bool) $value;
    }

    public function isProcessed() {
        return $this->_processed;
    }

    public function getParam($name, $default = null) {
        if (isset($this->_params[$name]))
            return $this->_params[$name];
        return $default;
    }

    public function getArg($index, $default = null) {
        if (isset($this->_args[$index]))
            return $this->_args[$index];
        return $default;
    }

}