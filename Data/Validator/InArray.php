<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy podana wartość zawiera się w tablicy
 */
class InArray extends ValidatorBase {

    /**
     * Komunikat o błędzie w przypadku gdy wartość nie jest tablicą
     */
    const MSG_NOT_IN_ARRAY = 'msgNotInArray';

    /**
     * Parametr ustawiający wartość od której wartość walidowana ma być większa
     */
    const OPT_ARRAY = 'optArray';

    public function __construct($options = null) {
        parent::__construct($options);

        if (!key_exists(self::OPT_ARRAY, $options)) {
            throw new exception("Brak kluczowego parametru " . self::OPT_ARRAY);
        }

        $this->_setMessagesTemplates([
            self::MSG_NOT_IN_ARRAY => "Wartość nie zawiera się w dopuszczalnych wartościach"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        $array = $this->_options[self::OPT_ARRAY];

        if (!in_array($value, $array)) {
            return $this->error(self::MSG_NOT_IN_ARRAY);
        }

        return true;
    }

}
