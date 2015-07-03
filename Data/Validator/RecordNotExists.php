<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy podana wartość nie istnieje jeszcze w bazie danych w podanym polu podanej tabeli
 */
class RecordNotExists extends RecordExists {
    
    public function __construct($options = null) {
        parent::__construct($options);
        
        $this->_setMessagesTemplates([
            self::ERR_RECORD_EXISTS => "Wpis już istnieje"
        ]);
    }

    public function isValid($value) {
        return !parent::isValid($value);
    }

}
