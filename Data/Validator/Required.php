<?php

namespace Skinny\Data\Validator;

/**
 * Walidator istnienia wybranego klucza w tablicy/obiekcie danych.
 * Klucz nie istnieje, jeżeli jego wartością jest obiekt klasy KeyNotExist
 */
class Required extends ValidatorBase {

    const MSG_REQUIRED = 'required';
    
    public function __construct($options = null) {
        parent::__construct($options);
        
        $this->_setMessagesTemplates([
            self::MSG_REQUIRED => 'Pole "%name%" jest wymagane'
        ]);
    }

    public function isValid($value) {
        if ($value instanceof \Skinny\Data\KeyNotExist) {
            $this->error(self::MSG_REQUIRED);
            return false;
        }
        return true;
    }

}
