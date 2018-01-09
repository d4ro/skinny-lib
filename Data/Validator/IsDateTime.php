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

        $pattern = '/^\d\d\d\d-(0?[1-9]|1[0-2])-(0?[1-9]|[12][0-9]|3[01]) (00|[0-9]|1[0-9]|2[0-3]):([0-9]|[0-5][0-9]):([0-9]|[0-5][0-9])$/';

        if (!preg_match($pattern, $value)) {
            $this->error(self::MSG_NOT_DATETIME);
            return false;
        }

        $datetimeExplode = explode(" ", $value);
        if (count($datetimeExplode) != 2) {
            $this->error(self::MSG_NOT_DATETIME);
            return false;
        }
        $dateExplode = explode("-", $datetimeExplode[0]);
        if (count($dateExplode) != 3) {
            $this->error(self::MSG_NOT_DATETIME);
            return false;
        }
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
