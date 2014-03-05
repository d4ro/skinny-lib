<?php

namespace Skinny\Db;

use Skinny\Db;

/**
 * Description of DatabaseAware
 *
 * @author Daro
 */
abstract class DatabaseAware {

    /**
     * Połączenie z bazą danych
     * @var Db
     */
    protected $_db;

    protected function getDb() {
        return $this->_db;
    }

}
