<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest emailem na podatawie metody filter_var
 */
class IsEmail extends IsString {

    /**
     * Komunikat zwracany w przypadku niepoprawnego adresu e-mail
     */
    const MSG_NOT_EMAIL = 'msgNotEmail';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_EMAIL => "Niepoprawny adres email"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->error(self::MSG_NOT_EMAIL);
        }
        return true;
    }

}
