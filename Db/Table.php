<?php

namespace Skinny\Db;

use Skinny\Db;

/**
 * Description of Table
 *
 * @author Daro
 */
class Table extends DatabaseAware {

    protected $_table;

    public function __construct(Db $db, $table) {
        $this->_db    = $db;
        $this->_table = $table;
    }

    public function sql($method = null) {
        return $this->_db->sql($method, $this->_table);
    }

    public function select($columns = null) {
        return $this->sql()->select($columns);
    }

    public function truncate() {
        // czyści tabelę
    }

}
