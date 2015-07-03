<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość istnieje już w bazie danych w podanym polu podanej tabeli
 */
class RecordExists extends ValidatorBase {

    /**
     * Komunikat zwracany w przypadku braku wpisu w bazie
     */
    const ERR_RECORD_NOT_EXISTS = 'ERR_RECORD_NOT_EXISTS';
    
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
            self::ERR_RECORD_NOT_EXISTS => "Brak wpisu w bazie danych"
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
        //$pattern = '/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/';
        $query = "select " . $this->_options[self::OPT_FIELD] . " from " . $this->_options[self::OPT_TABLE] . " where " . $this->_options[self::OPT_FIELD] . "='" . $value . "' limit 1";
        $result = $this->_options[self::OPT_DB]->fetchRow($query);
//        die(var_dump($result));

        if (!$result) {
            $this->error(self::ERR_RECORD_NOT_EXISTS);
            return false;
        }
        return true;
    }

}
