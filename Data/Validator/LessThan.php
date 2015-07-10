<?php

namespace Skinny\Data\Validator;

/**
 * Klasa sprawdzająca czy wartość wprowadzana jest większa od limitu
 */
class LessThan extends ValidatorBase {

    /**
     * Komunikat o błędzie w przypadku gdy wartość nie jest numeryczna
     */
    const MSG_IS_NOT_NUMERIC = 'isNotNumeric';

    /**
     * Komunikat o błędzie w przypadku gdy wartość nie jest mniejsza niż walidowana
     */
    const MSG_NOT_LESS_THEN = 'notLessThan';

    /**
     * Parametr ustawiający wartość od której wartość walidowana ma być mniejsza
     */
    const OPT_LESS_THAN = 'LessThan';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_options = $options;

        if (!$options[self::OPT_LESS_THAN]) {
            die($this->_option[self::OPT_LESS_THAN]);
            throw new exception("Brak kluczowego parametru " . self::OPT_LESS_THAN);
        }

        $this->_setMessagesTemplates([
            self::MSG_NOT_LESS_THEN => 'Wprowadzana wartość jest większa niż powinna',
            self::MSG_IS_NOT_NUMERIC => 'Wprowadzona wartość nie jest numeryczna'
        ]);
    }

    public function isValid($value) {
        if (!is_numeric($value)) {
            return $this->error(self::MSG_IS_NOT_NUMERIC);
        }

        if ($value > $this->_options[self::OPT_LESS_THAN]) {
            return $this->error(self::MSG_NOT_LESS_THEN);
        }

        return true;
    }

}
