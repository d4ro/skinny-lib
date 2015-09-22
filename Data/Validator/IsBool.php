<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy podana wartość jest booleanem.
 * 
 * UWAGA! Wartość będzie boolean dla:
 * - true
 * - false
 * - 1
 * - 0
 * 
 * - oraz dla strtolower:
 * - "true"
 * - "false"
 * - "yes"
 * - "no"
 * - "on"
 * - "y"
 * - "n"
 * - "tak"
 * - "nie"
 * - "t"
 * 
 * @todo Można stworzyć dodatkową opcję dla tego walidatora np. "strict" albo coś takiego,
 * która będzie sprawdzać TYLKO true, false, 0, 1 ??
 */
class IsBool extends ValidatorBase {

    /**
     * Ustawienie tej opcji na "true" powoduje sprawdzenie czy wartość jest dokładnie
     * true, false, 1, 0. <br/>
     * Domyślnie "false".
     */
    const OPT_STRICT = 'optStrict';
    const MSG_NOT_BOOL = 'msgNotBool';

    public function __construct($options = null) {
        if (empty($options) || !isset($options[self::OPT_STRICT])) {
            $options[self::OPT_STRICT] = false;
        }

        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_BOOL => "Nieprawidłowy typ danych. Oczekiwany typ: Boolean"
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        if (
                $value !== true &&
                $value !== false &&
                $value !== 1 &&
                $value !== 0
        ) {
            if (
                    $this->_options[self::OPT_STRICT] === false &&
                    is_string($value) && (
                        ($lower = strtolower($value)) === 'true' ||
                        $lower === 'false' ||
                        $lower === 'yes' ||
                        $lower === 'no' ||
                        $lower === 'on' ||
                        $lower === 'y' ||
                        $lower === 'n' ||
                        $lower === 'tak' ||
                        $lower === 'nie' ||
                        $lower === 't'
                    )
            ) {
                return true;
            }
            
            return $this->error(self::MSG_NOT_BOOL);
        }
        return true;
    }

}
