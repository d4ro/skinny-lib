<?php

namespace Skinny\Data\Validator;

/**
 * Walidator sprawdzający czy wartość istnieje już w bazie danych w podanym polu podanej tabeli
 */
class RecordExists extends ValidatorBase {

    const MSG_NOT_RECORDEXISTS = 'notRecordExists';
    protected $db;
    const OPT_DB = 'db';
    protected $table;
    const OPT_TABLE = 'table';
    protected $field;
    const OPT_FIELD = 'field';

    public function __construct($options = null) {
        parent::__construct($options);

        $this->_setMessagesTemplates([
            self::MSG_NOT_RECORDEXISTS => "Brak wpisu w bazie danych"
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
        $this->db = $this->_options['db'];
        $this->table = $this->_options['table'];
        $this->field = $this->_options['field'];
    }

    public function isValid($value) {
        //$pattern = '/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/';
        $query = "select ".$this->field." from ".$this->table." where ".$this->field."='".$value."' limit 1";
        $result = $this->db->fetchRow($query);
        die(var_dump($result));
        if (!$result) {
            $this->error(self::MSG_NOT_RECORDEXISTS);
            return false;
        }
        return true;
    }

}
