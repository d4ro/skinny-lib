<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym adresem internetowym
 */
class IsUrl extends ValidatorBase {

    const MSG_NOT_URL = 'notUrl';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_URL => "Niepoprawny adres internetowy"
        ]);
        
    }

    public function isValid($value) {
        if(!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->error(self::MSG_NOT_URL);
            return false;
        }
        return true;
    }

}
