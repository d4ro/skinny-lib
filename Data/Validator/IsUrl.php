<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym adresem internetowym
 * @todo uniwersalicacja, rozbudowa, parametryzacja
 */
class IsUrl extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku niepoprawnego adresu internetowego
     */
    const MSG_NOT_URL = 'msgNotUrl';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_URL => "Niepoprawny adres internetowy"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }
        
        if(!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->error(self::MSG_NOT_URL);
            return false;
        }
        return true;
    }

}
