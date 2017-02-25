<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawną datą wraz z godziną w formacie YYYY-mm-dd H:i:s
 */
class IsDateTime extends IsString {

    /**
     * Komunikat zwracany w przypadku niepoprawnego formatu daty i czasu
     */
    const MSG_NOT_DATETIME = 'msgNotDateTime';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_DATETIME => "Niepoprawny format daty i czasu"
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
         * Analogicznie z czasem...
         * 
         * DO POPRAWIENIA
         * TODO
         */
        $datetimeExplode = explode(" ", $value);
        $dateExplode = explode("-", $datetimeExplode[0]);
        if (!checkdate($dateExplode[1], $dateExplode[2], $dateExplode[0])) {
            $this->error(self::MSG_NOT_DATETIME);
            return false;
        }
        $timePattern = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
        if (!preg_match($timePattern, $datetimeExplode[1])) {
            return $this->error(self::MSG_NOT_DATETIME);
        }
        return true;
    }

}
