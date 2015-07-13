<?php

namespace Skinny\Data\Validator;

/**
 * Klasa sprawdzająca czy wartość jest niepustym łańcuchem znaków 
 * zawierającym tylko znaki dla aktualnego "locale".
 * 
 * Możliwość ustawienia ignorowania białych znaków w konstruktorze.
 */
class Alpha extends IsString {

    const MSG_IS_EMPTY = 'isEmpty';
    const MSG_NOT_ALPHA = 'notAlpha';
    const PRM_ALLOW_WHITESPACE = 'allowWhiteSpace';

    /**
     * Klasa sprawdzająca czy wartość jest niepustym łańcuchem znaków 
     * zawierającym tylko znaki dla aktualnego "locale".
     * 
     * Możliwość ustawienia ignorowania białych znaków w konstruktorze.
     * 
     * @param boolean $allowWhiteSpace Pozwalaj na białe znaki w tekście - domyślnie "false"
     */
    public function __construct($allowWhiteSpace = false) {
        $options = [
            self::PRM_ALLOW_WHITESPACE => $allowWhiteSpace
        ];

        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_IS_EMPTY => 'Łańcuch znaków jest pusty'
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        if (empty($value)) {
            return $this->error(self::MSG_IS_EMPTY);
        }

        if ($this->_options[self::PRM_ALLOW_WHITESPACE] === true) {
            // Jeżeli zezwalamy na białe znaki to rozbieramy z nich stringa przed walidacją ctype
            $value = preg_replace('/\s+/', '', $value);
        }

        if (!ctype_alpha($value)) {
            return $this->error(self::MSG_NOT_ALPHA);
        }

        return true;
    }

}
