<?php

namespace Skinny\Data\Validator;

class StringLength extends IsString {

    /**
     * Tekst jest zbyt krótki
     */
    const TOO_SHORT = 'tooShort';

    /**
     * Tekst jest zbyt długi
     */
    const TOO_LONG = 'tooLong';

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

    public function __construct(array $options) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::TOO_SHORT => 'Wartość pola "%name%" jest krótsza niż %min% znaków (Obecnie %currentLength% znaków)',
            self::TOO_LONG => 'Wartość pola "%name%" jest dłuższa niż %max% znaków (Obecnie %currentLength% znaków)'
        ]);

        if (key_exists('min', $this->_options)) {
            if (!is_int($this->_options['min'])) {
                throw new exception("'min' option has to be an integer");
            }
            $this->_min = $this->_options['min'];
            $this->setMessagesParams(['min' => $this->_min]);
        }
        if (key_exists('max', $this->_options)) {
            if (!is_int($this->_options['max'])) {
                throw new exception("'max' option has to be an integer");
            }
            $this->_max = $this->_options['max'];
            $this->setMessagesParams(['max' => $this->_max]);
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
            $this->error(self::TOO_SHORT);

            if ($this->_options[self::OPT_BREAK_ON_ERROR]) {
                return false;
            }
        }

        /**
         * Sprawdzenie maksymalnej długości stringa
         */
        if (isset($this->_max) && $this->_currentLength > $this->_max) {
            $this->error(self::TOO_LONG);

            if ($this->_options[self::OPT_BREAK_ON_ERROR]) {
                return false;
            }
        }

        return empty($this->_errors);
    }

}
