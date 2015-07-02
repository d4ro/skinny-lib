<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawną datą w formacie YYYY-mm-dd
 */
class IsDate extends ValidatorBase {

    const MSG_NOT_DATE = 'notDate';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_DATE => "Niepoprawna data"
        ]);
    }

    public function isValid($value) {
        //$pattern = '/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/';
        $dateExplode = explode("-", $value);
        if (!checkdate($dateExplode[1],$dateExplode[2],$dateExplode[0])) {
            $this->error(self::MSG_NOT_DATE);
            return false;
        }
        return true;
    }

}
