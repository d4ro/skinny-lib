<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym numerem NIP (nie pozwala na "-")
 */
class IsNip extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku niepoprawnego NIPu
     */
    const MSG_NOT_NIP = 'msgNotNip';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_NIP => "Niepoprawny numer NIP"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        /**
         * BUG! TODO:
         * Np. Walidator isNUmeric po którym ten będzie dziedziczył! 
         * Wartość przecież może być czymkolwiek, np. tablicą i wtedy strlen, preg_match
         * zrzuci warning.
         * 
         * DO POPRAWIENIA
         * TODO
         */
        // sprawdzamy czy długość stringa to 10 i czy składa się tylko z cyfr
        if (strlen($value) != 10 || !preg_match('/^[0-9\ ]+$/', $value)) {
            $this->error(self::MSG_NOT_NIP);
            return false;
        }

        // tablica z odpowiednimi wagami
        $arrSteps = array(6, 5, 7, 2, 3, 4, 5, 6, 7);
        $intSum = 0;
        for ($i = 0; $i < 9; $i++) {
            // mnożymy każdy ze znaków przez wagę i sumujemy wszystko
            $intSum += $arrSteps[$i] * $value[$i];
        }

        $int = $intSum % 11;
        $intControlNr = ($int == 10) ? 0 : $int;

        // sprawdzamy czy taka sama suma kontrolna jest w ciągu
        if ($intControlNr == $value[9]) {
            return true;
        }

        return $this->error(self::MSG_NOT_NIP);
    }

}
