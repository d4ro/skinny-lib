<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym adresem IP
 */
class IsIp extends ValidatorBase {

    const MSG_NOT_IP = 'notIp';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_IP => "Niepoprawny adres IP"
        ]);
    }

    public function isValid($value) {
        $pattern = '/^((([01]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))[.]){3}(([0-1]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))$/';
        if (!preg_match($pattern,$value)) {
            $this->error(self::MSG_NOT_IP);
            return false;
        }
        return true;
    }

}
