<?php

namespace Skinny\Data\Validator;

/**
 * Walidator jest połączeniem sprawdzenia dwóch walidatorów MustExist oraz NotEmpty.
 * 
 * Uwaga! Używany jest stricte przez klasę Validate i nie ma on sensu jako
 * odrębny walidator.
 */
class Required extends ValidatorBase {

    const MSG_REQUIRED = 'required';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_REQUIRED => 'Pole "%name%" jest wymagane'
        ]);
    }

    public function isValid($value) {
        if (
                $value instanceof \Skinny\Data\KeyNotExist ||
                false === (new NotEmpty())->isValid($value)
        ) {
            $this->error(self::MSG_REQUIRED);
            return false;
        }
        return true;
    }

}
