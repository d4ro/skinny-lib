<?php

namespace Skinny\Data\Validator;

/**
 * Walidator porównujący wartości
 * 
 * @todo Można w razie potrzeby rozbudować o opcję porównania wielu wartości (tablica danych)
 */
class IsEqual extends ValidatorBase {

    const OPT_VALUE_TO_COMPARE = 'valueToCompare';
    
    const MSG_NOT_EQUAL = 'notEqual';
    
    const PRM_VALUE_TO_COMPARE = '%valueToCompare%';

    /**
     * Dowolna wartość do porównania
     * @var mixed
     */
    protected $_valueToCompare = null;

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_EQUAL => "Podane wartości nie są identyczne"
        ]);

        if (key_exists(self::OPT_VALUE_TO_COMPARE, $this->_options)) {
            // Ustawienie wartości do porównania
            $this->_valueToCompare = $this->_options[self::OPT_VALUE_TO_COMPARE];

            // Ustawienie parametru do komunikatów przechowującego wartość do porównania
            $this->setMessagesParams([
                self::OPT_VALUE_TO_COMPARE => $this->_valueToCompare
            ]);
        }
    }

    public function isValid($value) {
        parent::isValid($value);

        if ($value !== $this->_valueToCompare) {
            // Ustawienie błędu dla odpowiedniego klucza komunikatu
            $this->error(self::MSG_NOT_EQUAL);
            return false;
        }
        return true;
    }

}
