<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest liczbą
 */
class IsInt extends ValidatorBase {

    const MSG_NOT_INT = 'msgNotInt';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_INT => "Wartość nie jest liczbą całkowitą",
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        /**
         * BUG!
         * Jeżeli wartość nie jest intem ani stringiem preg_match zrzuci Warning...
         * Poprawione
         */
        if (
                !is_int($value) &&
                (!is_string($value) || !preg_match('/^-?[0-9]{1,}$/', $value))
        ) {
            return $this->error(self::MSG_NOT_INT);
        }

        return true;
    }

}
