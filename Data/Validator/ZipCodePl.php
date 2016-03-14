<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym numerem kodu pocztowego w Polsce
 */
class ZipCodePl extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku niepoprawnego kodu pocztowego
     */
    const MSG_NOT_ZIP_CODE = 'msgNotZipCode';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_ZIP_CODE => "Niepoprawny format kodu pocztowego"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }
        
        /**
         * BUG!
         * Znowu analogiczna sytuacja - preg_match operuje na stringu a tu wartość
         * może być dowolna...
         * DO POPRAWIENIA
         * TODO
         */

        if (!(preg_match('/^[0-9]{5}$/', $value)) && !(preg_match('/^([0-9]{2})-([0-9]{3})$/', $value))) {
            return $this->error(self::MSG_NOT_ZIP_CODE);
        }

        return true;
    }

}
