<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawnym adresem IP
 * @todo Obsługa wersji ipv6
 */
class IsIp extends IsString {

    /**
     * Komunikat zwracany w przypadku niepoprawnego formatu adresu IP
     */
    const MSG_NOT_IP = 'msgNotIp';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_IP => "Niepoprawny adres IP"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }
        
        $pattern = '/^((([01]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))[.]){3}(([0-1]?[0-9]{1,2})|(2[0-4][0-9])|(25[0-5]))$/';
        if (!preg_match($pattern, $value)) {
            return $this->error(self::MSG_NOT_IP);
        }
        return true;
    }

}
