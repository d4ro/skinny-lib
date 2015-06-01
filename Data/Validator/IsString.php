<?php

namespace Skinny\Data\Validator;

/**
 * @todo obsługa możliwości podania obecnego typu do komunikatu np.
 * "Oczekiwano: String, aktualnie %type%"
 */
class IsString extends ValidatorBase {
    
    const NOT_STRING = 'notString';
    
    public function __construct($options = null) {
        parent::__construct($options);
        
        $this->_setMessagesTemplates([
            self::NOT_STRING => "Nieprawidłowy typ danych. Oczekiwany typ: String"
        ]);
    }

    public function isValid($value) {
        if (!is_string($value)) {
            $this->error(self::NOT_STRING);
            return false;
        }
        return true;
    }

}
