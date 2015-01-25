<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Skinny\Data\Validator;

/**
 * Description of ValidatorBase
 *
 * @author Daro
 */
abstract class ValidatorBase extends \Skinny\Validation\Usable {

    protected $_lastValidatedObject;
    protected $_messages;

    protected abstract function validate($object);

    public function isValidated($object) {
        return $object === $this->_lastValidatedObject;
    }

    public function isValid($object) {
        if ($this->isValidated($object)) {
            return empty($this->_messages);
        }

        $this->_lastValidatedObject = $object;
        $this->_messages = array();

        $this->validate($object);
        return empty($this->_messages);
    }

    protected function useOn($object) {
        return $this->validate($object);
    }

}
