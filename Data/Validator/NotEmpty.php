<?php

namespace Skinny\Data\Validator;

class NotEmpty extends ValidatorBase {

    const IS_EMPTY = 'isEmpty';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::IS_EMPTY => 'Wartość jest pusta'
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        if (
            $value === null ||
            (is_string($value) && strlen($value) === 0) ||
            (is_array($value) && count($value) === 0)
        ) {
            $this->error(self::IS_EMPTY);
            return false;
        }
        return true;
    }

}
