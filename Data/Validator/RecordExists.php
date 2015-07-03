<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość istnieje już w bazie danych w podanym polu podanej tabeli
 */
class RecordExists extends ValidatorBase {

    const MSG_RECORD_NOT_EXISTS = 'notRecordExists';
    const OPT_DB = 'db';
    const OPT_TABLE = 'table';

    /**
     * Opcja ustawiająca które pole ma być sprawdzone w bazie
     */
    const OPT_FIELD = 'field';

    protected $_db;
    protected $_table;
    protected $_field;

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_RECORD_NOT_EXISTS => "Brak wpisu w bazie danych"
        ]);

        if (!$this->_options['db']) {
            throw new exception("Błąd połączenia z bazą");
        }
        if (!$this->_options['table']) {
            throw new exception("Nie podano tabeli");
        }
        if (!$this->_options['field']) {
            throw new exception("Nie podano pola");
        }

        $this->_db = $this->_options['db'];
        $this->_table = $this->_options['table'];
        $this->_field = $this->_options['field'];
    }

    public function isValid($value) {
        //$pattern = '/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/';
        $query = "select " . $this->_field . " from " . $this->_table . " where " . $this->_field . "='" . $value . "' limit 1";
        $result = $this->_db->fetchRow($query);
//        die(var_dump($result));

        if (!$result) {
            $this->error(self::MSG_RECORD_NOT_EXISTS);
            return false;
        }
        return true;
    }

}
