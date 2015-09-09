<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym numerem kodu pocztowego w Polsce
 */
class zipCodePl extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku niepoprawnego kodu pocztowego
     */
    const MSG_NOT_ZIPCODE = 'notZipCode';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_ZIPCODE => "Niepoprawny format kodu pocztowego"
        ]);
    }

    public function isValid($value) {
        if ((preg_match('/^[0-9]{5}$/', $value)) || (preg_match('/^([0-9]{2})-([0-9]{3})$/', $value))) {
            return true;
        } else {
            $this->error(self::MSG_NOT_ZIPCODE);
            return false;
        }
    }

}
