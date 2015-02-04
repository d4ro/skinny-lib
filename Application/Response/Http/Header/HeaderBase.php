<?php

namespace Skinny\Application\Response\Http\Header;

/**
 * Description of HeaderBase
 *
 * @author Daro
 */
abstract class HeaderBase implements HeaderInterface {

    protected $_name;
    protected $_value;
    protected $_code;

    public function __construct() {
        $this->_name = '';
        $this->_value = '';
        $this->_code = null;
    }

    public function getCode() {
        return $this->_code;
    }

    public function getName() {
        return $this->_name;
    }

    public function getValue() {
        return $this->_value;
    }

    public function toString() {
        return $this->getName() . ": " . $this->getValue();
    }

}
