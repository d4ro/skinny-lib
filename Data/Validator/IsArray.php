<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy podana wartość jest tablicą
 */
class IsArray extends ValidatorBase {

    const MSG_NOT_ARRAY = 'msgNotArray';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_ARRAY => "Nieprawidłowy typ danych. Oczekiwany typ: Array"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        if (!is_array($value)) {
            return $this->error(self::MSG_NOT_ARRAY);
        }
        return true;
    }

}
