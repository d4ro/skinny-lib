<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawną datą wraz z godziną w formacie YYYY-mm-dd H:i:s
 */
class IsDatetime extends ValidatorBase {

    const MSG_NOT_DATETIME = 'notDatetime';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_DATETIME => "Niepoprawny format daty i czasu"
        ]);
    }

    public function isValid($value) {
        $datetimeExplode = explode(" ", $value);
        $dateExplode = explode("-", $datetimeExplode[0]);
        if (!checkdate($dateExplode[1],$dateExplode[2],$dateExplode[0])) {
            $this->error(self::MSG_NOT_DATETIME);
            return false;
        }
        $timePattern = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
        if (!preg_match($timePattern,$datetimeExplode[1])) {
            $this->error(self::MSG_NOT_DATETIME);
            return false;
        }
        return true;
    }

}
