<?php

namespace Skinny\Data\Validator;

class StringLength extends IsString {

    /**
     * Minimalna ilość znaków dla stringa
     */
    const OPT_MIN = 'optMin';

    /**
     * Maksymalna ilość znaków dla stringa
     */
    const OPT_MAX = 'optMax';

    /**
     * Czy dane wejściowe mają zostać ztrimowane
     */
    const OPT_TRIM_INPUT = 'optTrimInput';

    /**
     * String jest zbyt krótki
     */
    const MSG_TOO_SHORT = 'msgTooShort';

    /**
     * String jest zbyt długi
     */
    const MSG_TOO_LONG = 'msgTooLong';

    /**
     * Parametr min (komunikaty)
     */
    const PRM_MIN = '%min%';

    /**
     * Parametr max (komunikaty)
     */
    const PRM_MAX = '%max%';

    /**
     * Parametr bieżącej długości łańcucha znaków (komunikaty)
     */
    const PRM_CURRENT_LENGTH = '%currentLength%';

    /**
     * Przechowuje aktualną długość stringa
     * @var int
     */
    protected $_currentLength = null;

    /**
     * Walidator długości łańcucha znaków.
     * 
     * @param array $options
     * @throws exception
     */
    public function __construct(array $options) {
        parent::__construct($options);

        if (key_exists(self::OPT_MIN, $this->_options)) {
            if (!is_int($this->_options[self::OPT_MIN])) {
                throw new exception("Invalid option: '" . self::OPT_MIN . "'. Integer expected.");
            }
            $this->setMessagesParams([
                self::PRM_MIN => $this->_options[self::OPT_MIN]
            ]);
        }
        if (key_exists(self::OPT_MAX, $this->_options)) {
            if (!is_int($this->_options[self::OPT_MAX])) {
                throw new exception("Invalid option: '" . self::OPT_MAX . "'. Integer expected.");
            }
            $this->setMessagesParams([
                self::PRM_MAX => $this->_options[self::OPT_MAX]
            ]);
        }

        if (!isset($this->_options[self::OPT_MIN]) && !isset($this->_options[self::OPT_MAX])) {
            throw new exception("At least one option has to be set.");
        }

        if (key_exists(self::OPT_TRIM_INPUT, $this->_options)) {
            if (!is_bool($this->_options[self::OPT_TRIM_INPUT])) {
                throw new exception("Invalid option: '" . self::OPT_TRIM_INPUT . "'. Boolean expected.");
            }
        } else {
            $this->_options[self::OPT_TRIM_INPUT] = false;
        }

        // Ustawienie komunikatów
        $this->_setMessagesTemplates([
            self::MSG_TOO_SHORT => "Tekst jest za krótki. Minimalna ilość znaków: " . self::PRM_MIN,
            self::MSG_TOO_LONG => "String jest za długi. Maksymalna ilość znaków: " . self::PRM_MAX
        ]);
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        if ($this->_options[self::OPT_TRIM_INPUT]) {
            $value = trim($value);
        }

        $this->_currentLength = strlen($value); // Ustawienie bieżącej długości stringa
        $this->setMessagesParams(['currentLength' => $this->_currentLength]); // Ustawienie bieżącej długości stringa
        // Sprawdzenie minimalnej długości stringa
        if (
                isset($this->_options[self::OPT_MIN]) &&
                $this->_currentLength < $this->_options[self::OPT_MIN]
        ) {
            $this->error(self::MSG_TOO_SHORT);

            if ($this->_options[self::OPT_BREAK_ON_ERROR]) {
                return false;
            }
        }

        // Sprawdzenie maksymalnej długości stringa
        if (
                isset($this->_options[self::OPT_MAX]) &&
                $this->_currentLength > $this->_options[self::OPT_MAX]
        ) {
            $this->error(self::MSG_TOO_LONG);

            if ($this->_options[self::OPT_BREAK_ON_ERROR]) {
                return false;
            }
        }

        return empty($this->_errors);
    }

}
