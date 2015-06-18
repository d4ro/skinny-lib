<?php

namespace Skinny\Data\Validator;

class StringLength extends IsString {

    /**
     * Minimalna ilość znaków dla stringa
     */
    const OPT_MIN = 'min';

    /**
     * Maksymalna ilość znaków dla stringa
     */
    const OPT_MAX = 'max';

    /**
     * String jest zbyt krótki
     */
    const MSG_TOO_SHORT = 'tooShort';

    /**
     * String jest zbyt długi
     */
    const MSG_TOO_LONG = 'tooLong';

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
     * Minimalna długość stringa
     * @var int
     */
    protected $_min = null;

    /**
     * Maksymalna długość stringa
     * @var int
     */
    protected $_max = null;

    /**
     * Walidator długości łańcucha znaków.
     * 
     * @param array $options
     * @throws exception
     */
    public function __construct(array $options) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_TOO_SHORT => "Tekst jest za krótki. Minimalna ilość znaków: %min%",
            self::MSG_TOO_LONG => "String jest za długi. Maksymalna ilość znaków: %max%"
        ]);

        if (key_exists(self::OPT_MIN, $this->_options)) {
            if (!is_int($this->_options[self::OPT_MIN])) {
                throw new exception("'min' option has to be an integer");
            }
            $this->_min = $this->_options[self::OPT_MIN];
            $this->setMessagesParams([self::OPT_MIN => $this->_min]);
        }
        if (key_exists(self::OPT_MAX, $this->_options)) {
            if (!is_int($this->_options[self::OPT_MAX])) {
                throw new exception("'max' option has to be an integer");
            }
            $this->_max = $this->_options[self::OPT_MAX];
            $this->setMessagesParams([self::OPT_MAX => $this->_max]);
        }

        if (!isset($this->_min) && !isset($this->_max)) {
            throw new exception("Invalid options");
        }
    }

    public function isValid($value) {
        if (!parent::isValid($value)) {
            return false;
        }

        $this->_currentLength = strlen($value); // Ustawienie bieżącej długości stringa
        $this->setMessagesParams(['currentLength' => $this->_currentLength]); // Ustawienie bieżącej długości stringa

        /**
         * Sprawdzenie minimalnej długości stringa
         */
        if (isset($this->_min) && $this->_currentLength < $this->_min) {
            $this->error(self::MSG_TOO_SHORT);

            if ($this->_options[self::OPT_BREAK_ON_ERROR]) {
                return false;
            }
        }

        /**
         * Sprawdzenie maksymalnej długości stringa
         */
        if (isset($this->_max) && $this->_currentLength > $this->_max) {
            $this->error(self::MSG_TOO_LONG);

            if ($this->_options[self::OPT_BREAK_ON_ERROR]) {
                return false;
            }
        }

        return empty($this->_errors);
    }

}
