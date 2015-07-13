<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest liczbą
 */
class IsInt extends ValidatorBase {

    const MSG_NOT_INT = 'notInt';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_INT => "Wartość nie jest liczbą",
        ]);
    }

    public function isValid($value) {
        
        if (!preg_match('/^-?[0-9]{1,}$/', $value)) {
            $this->error(self::MSG_NOT_INT);
            return false;
        }

        return true;
    }

}
