<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawną datą w formacie YYYY-mm-dd
 */
class IsDate extends IsString {

    /**
     * Komunikat zwracany w przypadku niepoprawnego formatu daty
     */
    const MSG_NOT_DATE = 'msgNotDate';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_DATE => "Niepoprawna data"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        /**
         * BUG!
         * Wartość może być czymkolwiek, np. tablicą i explode nie zadziała,
         * tzn. zostanie zrzucony NOTICE.
         * 
         * Walidator powinien dziedziczyć po isString (Poprawiłem)
         * 
         * Poza tym jeżeli nawet jest stringiem to należałoby sprawdzić czy tablica
         * po explode zawiera 3 wartości (i to raczej dokładnie 3),
         * w przeciwnym wypadku coś z wartością jest jednak nie tak...
         * 
         * DO POPRAWIENIA
         * TODO
         */
        $dateExplode = explode("-", $value);
        if (count($dateExplode) < 3 || !checkdate($dateExplode[1], $dateExplode[2], $dateExplode[0])) {
            return $this->error(self::MSG_NOT_DATE);
        }

        return true;
    }

}
