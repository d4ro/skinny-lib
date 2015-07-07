<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość nie znajduje się już w bazie danych w podanym polu podanej tabeli
 */
class RecordNotExists extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku występowania wpisu w bazie
     */
    const ERR_RECORD_EXISTS = 'ERR_RECORD_EXISTS';
    
    /**
     * Opcja przetrzymująca połączenie z bazą
     */
    const OPT_DB = 'OPT_DB';
    
    /**
     * Opcja ustawiająca w której tabeli znajduje się sprawdzane pole
     */
    const OPT_TABLE = 'OPT_TABLE';

    /**
     * Opcja ustawiająca które pole ma być sprawdzone w bazie
     */
    const OPT_FIELD = 'OPT_FIELD';


    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::ERR_RECORD_EXISTS => "Rekord już istnieje"
        ]);

        if (!$this->_options[self::OPT_DB]) {
            throw new exception("Brak kluczowej opcji OPT_DB");
        }
        if (!$this->_options[self::OPT_TABLE]) {
            throw new exception("Brak kluczowej opcji OPT_TABLE");
        }
        if (!$this->_options[self::OPT_FIELD]) {
            throw new exception("Brak kluczowej opcji OPT_FIELD");
        }
    }

    public function isValid($value) {
        $select = $this->_options[self::OPT_DB]->select();
        $select->from($this->_options[self::OPT_TABLE],$this->_options[self::OPT_FIELD]);
        $select->where($this->_options[self::OPT_FIELD]."=?",$value);
        $select->limit(1);
        $result = $this->_options[self::OPT_DB]->fetchRow($select);
        
        if ($result) {
            $this->error(self::ERR_RECORD_EXISTS);
            return false;
        }
        return true;
    }
}