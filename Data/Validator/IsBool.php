<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy podana wartość jest booleanem.
 * 
 * UWAGA! Wartość będzie boolean dla:
 * - true
 * - false
 * - 1
 * - 0
 * 
 * - oraz dla strtolower:
 * - "true"
 * - "false"
 * - "yes"
 * - "no"
 * - "on"
 * - "y"
 * - "n"
 * - "tak"
 * - "nie"
 * - "t"
 * 
 * @todo Można stworzyć dodatkową opcję dla tego walidatora np. "strict" albo coś takiego,
 * która będzie sprawdzać TYLKO true, false, 0, 1 ??
 */
class IsBool extends ValidatorBase {

    const MSG_NOT_BOOL = 'notBool';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_BOOL => "Nieprawidłowy typ danych. Oczekiwany typ: Boolean"
        ]);
    }

    public function isValid($value) {
        parent::isValid($value);
        
        if (
                $value !== true &&
                $value !== false &&
                $value !== 1 &&
                $value !== 0 &&
                ($lower = strtolower($value)) !== 'true' &&
                $lower !== 'false' &&
                $lower !== 'yes' &&
                $lower !== 'no' &&
                $lower !== 'on' &&
                $lower !== 'y' &&
                $lower !== 'n' &&
                $lower !== 'tak' &&
                $lower !== 'nie' &&
                $lower !== 't'
        ) {
            $this->error(self::MSG_NOT_BOOL);
            return false;
        }
        return true;
    }

}
