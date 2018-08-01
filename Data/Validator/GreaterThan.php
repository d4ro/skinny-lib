<?php

namespace Skinny\Data\Validator;

/**
 * Klasa sprawdzająca czy wartość wprowadzana jest większa od limitu
 */
class GreaterThan extends ValidatorBase {

    /**
     * Komunikat o błędzie w przypadku gdy wartość nie jest numeryczna
     */
    const MSG_IS_NOT_NUMERIC = 'msgIsNotNumeric';

    /**
     * Komunikat o błędzie w przypadku gdy wartość nie jest większa niż walidowana
     */
    const MSG_NOT_GREATER_THEN = 'msgNotGreaterThan';

    /**
     * Parametr ustawiający wartość od której wartość walidowana ma być większa
     */
    const OPT_GREATER_THAN = 'optGreaterThan';

    public function __construct($options = null) {
        parent::__construct($options);

        if (!key_exists(self::OPT_GREATER_THAN, $options)) {
            throw new exception("Brak kluczowego parametru " . self::OPT_GREATER_THAN);
        }

        $this->_setMessagesTemplates([
            self::MSG_NOT_GREATER_THEN => 'Wprowadzana wartość jest mniejsza niż powinna',
            self::MSG_IS_NOT_NUMERIC   => 'Wprowadzona wartość nie jest numeryczna'
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        if (!is_numeric($value)) {
            return $this->error(self::MSG_IS_NOT_NUMERIC);
        }

        if ($value < $this->_options[self::OPT_GREATER_THAN]) {
            return $this->error(self::MSG_NOT_GREATER_THEN);
        }

        return true;
    }

}
