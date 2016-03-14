<?php

namespace Skinny\Data\Validator;

class IsString extends ValidatorBase {
    
    const MSG_NOT_STRING = 'msgNotString';
    
    public function __construct($options = null) {
        parent::__construct($options);
        
        $this->_setMessagesTemplates([
            self::MSG_NOT_STRING => "Nieprawidłowy typ danych. Oczekiwany typ: String"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }
        
        if (!is_string($value)) {
            $this->error(self::MSG_NOT_STRING);
            return false;
        }
        return true;
    }

}
