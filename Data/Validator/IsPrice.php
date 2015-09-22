<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawną kwotą (zarówno z przecinkiem jak i z kropką)
 */
class IsPrice extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku niepoprawnego formatu ceny
     */
    const MSG_NOT_PRICE = 'msgNotPrice';

    /**
     * Opcja określająca jaki znak jest separatorem miejsc dziesiętnych
     */
    const OPT_SEPARATOR = 'separator';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_PRICE => "Niepoprawna kwota"
        ]);
        
        if (key_exists(self::OPT_SEPARATOR, $this->_options)) {
            if ($this->_options[self::OPT_SEPARATOR] != "," && $this->_options[self::OPT_SEPARATOR] != ".") {
                throw new exception("Podano zły separator");
            }
        } else {
            $this->_options[self::OPT_SEPARATOR] = ".";
        }
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }
        
        /**
         * BUG!
         * TODO DO POPRAWIENIA
         * Warning w przypadku gdy value nie jest string
         */

        $separator = ($this->_options[self::OPT_SEPARATOR] == '.' ? '\.' : $this->_options[self::OPT_SEPARATOR]);
        $pattern = '/^(?:0|[1-9]\d*)(?:' . $separator . '\d{2})?$/';
        if (!preg_match($pattern, $value)) {
            return $this->error(self::MSG_NOT_PRICE);
        }
        return true;
    }

}
