<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość jest poprawną kwotą (zarówno z przecinkiem jak i z kropką)
 */
class IsPrice extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku niepoprawnego formatu ceny
     */
    const MSG_NOT_PRICE = 'notPrice';
    
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
            if ($this->_options[self::OPT_SEPARATOR]!="," && $this->_options[self::OPT_SEPARATOR]!=".") {
                throw new exception("Podano zły separator");
            }
        }else{
            $this->_options[self::OPT_SEPARATOR]=".";
        }
    }

    public function isValid($value) {
        $this->_options[self::OPT_SEPARATOR] = ($this->_options[self::OPT_SEPARATOR] == '.' ? '\.' : $this->_options[self::OPT_SEPARATOR]);
        $pattern = '/^(?:0|[1-9]\d*)(?:' . $this->_options[self::OPT_SEPARATOR] . '\d{2})?$/';
        if (!preg_match($pattern,$value)) {
            $this->error(self::MSG_NOT_PRICE);
            return false;
        }
        return true;
    }

}
