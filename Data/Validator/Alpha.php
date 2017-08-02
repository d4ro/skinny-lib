<?php

namespace Skinny\Data\Validator;

/**
 * Klasa sprawdzająca czy wartość jest niepustym łańcuchem znaków 
 * zawierającym tylko znaki dla aktualnego "locale".
 * 
 * Możliwość ustawienia ignorowania białych znaków w konstruktorze.
 */
class Alpha extends IsString {

    const MSG_NOT_ALPHA        = 'msgNotAlpha';
    const OPT_ALLOW_WHITESPACE = 'optAllowWhiteSpace';

    /**
     * Klasa sprawdzająca czy wartość jest niepustym łańcuchem znaków 
     * zawierającym tylko znaki dla aktualnego "locale".
     * 
     * Możliwość ustawienia ignorowania białych znaków opcjach.
     * 
     * @param boolean $options Tablica opcji dla tego walidatora
     */
    public function __construct($options = null) {
        // ustawienie domyślnej opcji jeśli nie przekazano
        if (empty($options) || !isset($options[self::OPT_ALLOW_WHITESPACE])) {
            $options[self::OPT_ALLOW_WHITESPACE] = false;
        }

        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_ALPHA => 'Łańcuch zawiera nieprawidłowe znaki'
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        if ($this->_options[self::OPT_ALLOW_WHITESPACE] === true) {
            // Jeżeli zezwalamy na białe znaki to rozbieramy z nich stringa przed walidacją ctype
            $value = preg_replace('/\s+/', '', $value);
        }

        if (!ctype_alpha($value)) {
            return $this->error(self::MSG_NOT_ALPHA);
        }

        return true;
    }

}
