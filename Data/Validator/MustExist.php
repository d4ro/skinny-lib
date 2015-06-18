<?php

namespace Skinny\Data\Validator;

/**
 * Walidator istnienia wybranego klucza w tablicy/obiekcie danych.
 * Klucz nie istnieje, jeżeli jego wartością jest obiekt klasy KeyNotExist.
 * 
 * Uwaga! Używany jest stricte przez klasę Validate i nie ma on sensu jako
 * odrębny walidator.
 */
class MustExist extends ValidatorBase {

    const MSG_MUST_EXIST = 'mustExist';
    
    public function __construct($options = null) {
        parent::__construct($options);
        
        $this->_setMessagesTemplates([
            self::MSG_MUST_EXIST => 'Klucz %name% nie istnieje'
        ]);
    }

    public function isValid($value) {
        if ($value instanceof \Skinny\Data\KeyNotExist) {
            $this->error(self::MSG_MUST_EXIST);
            return false;
        }
        return true;
    }

}
