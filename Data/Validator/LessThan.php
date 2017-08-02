<?php

namespace Skinny\Data\Validator;

/**
 * Klasa sprawdzająca czy wartość wprowadzana jest większa od limitu
 * @todo Błędna nazwa? Powinno być raczej LowerThan
 */
class LessThan extends ValidatorBase {

    /**
     * Komunikat o błędzie w przypadku gdy wartość nie jest numeryczna
     */
    const MSG_IS_NOT_NUMERIC = 'msgIsNotNumeric';

    /**
     * Komunikat o błędzie w przypadku gdy wartość nie jest mniejsza niż walidowana
     */
    const MSG_NOT_LESS_THAN = 'msgNotLessThan';

    /**
     * Parametr ustawiający wartość od której wartość walidowana ma być mniejsza
     */
    const OPT_LESS_THAN = 'optLessThan';

    public function __construct($options = null) {
        if (!parent::isValid($value)) {
            return false;
        }

        /**
         * BUG!
         * Zostawione die i niezabezpieczone dane, wygląda jakby to było nieskończone
         * 
         * DO POPRAWIENIA
         * TODO 
         */
        throw new Exception('Walidator błędnie skonstruowany - do poprawy');

        parent::__construct($options);

        $this->_options = $options;

        if (!$options[self::OPT_LESS_THAN]) {
            throw new exception("Brak kluczowego parametru " . self::OPT_LESS_THAN);
        }

        $this->_setMessagesTemplates([
            self::MSG_NOT_LESS_THAN  => 'Wprowadzana wartość jest większa niż powinna',
            self::MSG_IS_NOT_NUMERIC => 'Wprowadzona wartość nie jest numeryczna'
        ]);
    }

    public function isValid($value) {
        if (!is_numeric($value)) {
            return $this->error(self::MSG_IS_NOT_NUMERIC);
        }

        if ($value > $this->_options[self::OPT_LESS_THAN]) {
            return $this->error(self::MSG_NOT_LESS_THAN);
        }

        return true;
    }

}
