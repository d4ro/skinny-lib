<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest emailem na podatawie metody filter_var
 */
class IsEmail extends ValidatorBase {

    const MSG_NOT_EMAIL = 'notEmail';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_EMAIL => "Niepoprawny adres email"
        ]);
    }

    public function isValid($value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->error(self::MSG_NOT_EMAIL);
            return false;
        }
        return true;
    }

}
