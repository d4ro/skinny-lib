<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym numerem pesel
 */
class IsPesel extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku niepoprawnego numeru pesel
     */
    const MSG_NOT_PESEL = 'msgNotPesel';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_PESEL => "Niepoprawny pesel"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        /**
         * BUG! TODO:
         * Walidator isNUmeric po którym ten będzie dziedziczył! 
         * Wartość przecież może być czymkolwiek, np. tablicą i wtedy strlen, preg_match
         * zrzuci warning.
         * 
         * DO POPRAWIENIA
         * TODO
         */
        // sprawdzamy czy ciąg ma 11 cyfr
        if (!preg_match('/^[0-9]{11}$/', $value)) {
            $this->error(self::MSG_NOT_PESEL);
            return false;
        }

        // tablica z odpowiednimi wagami
        $arrSteps = array(1, 3, 7, 9, 1, 3, 7, 9, 1, 3);
        $intSum   = 0;
        for ($i = 0; $i < 10; $i++) {
            // mnożymy każdy ze znaków przez wagę i sumujemy wszystko
            $intSum += $arrSteps[$i] * $value[$i];
        }

        // obliczamy sumę kontrolną
        $int          = 10 - $intSum % 10;
        $intControlNr = ($int == 10) ? 0 : $int;

        // TODO: brak konwencji w zwracaniu wyników raz jest sprawdzenie czy nie ok
        // i zwócenie false a raz na odwrót.
        // Powinna być konwencja pierwsza.
        // Po wprowadzeniu zmian proszę usunąć konentarze ;)
        // sprawdzamy czy taka sama suma kontrolna jest w ciągu
        if ($intControlNr == $value[10]) {
            return true;
        }

        return $this->error(self::MSG_NOT_PESEL);
    }

}
