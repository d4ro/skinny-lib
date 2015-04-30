<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy podana wartość jest tablicą
 */
class IsArray extends ValidatorBase {

    const MSG_NOT_ARRAY = 'notArray';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_ARRAY => "Nieprawidłowy typ danych. Oczekiwany typ: Array"
        ]);
    }

    public function isValid($value) {
        parent::isValid($value);
        
        if (!is_array($value)) {
            $this->error(self::MSG_NOT_ARRAY);
            return false;
        }
        return true;
    }

}
