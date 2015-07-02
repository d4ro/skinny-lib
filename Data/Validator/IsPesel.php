<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym numerem pesel
 */
class IsPesel extends ValidatorBase {

    const MSG_NOT_PESEL = 'notPesel';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_PESEL => "Niepoprawny pesel"
        ]);
    }

    public function isValid($value) {
        if (!preg_match('/^[0-9]{11}$/', $value)) { // sprawdzamy czy ciąg ma 11 cyfr
            $this->error(self::MSG_NOT_PESEL);
            return false;
        }
        $arrSteps = array(1, 3, 7, 9, 1, 3, 7, 9, 1, 3); // tablica z odpowiednimi wagami
        $intSum = 0;
        for ($i = 0; $i < 10; $i++) {
            $intSum += $arrSteps[$i] * $value[$i]; // mnożymy każdy ze znaków przez wagę i sumujemy wszystko
        }
        $int = 10 - $intSum % 10; // obliczamy sumę kontrolną
        $intControlNr = ($int == 10) ? 0 : $int;
        if ($intControlNr == $value[10]) { // sprawdzamy czy taka sama suma kontrolna jest w ciągu
            return true;
        }
        $this->error(self::MSG_NOT_PESEL);
        return false;
    }

}
