<?php

namespace Skinny\Db;

use Skinny\Db;

/**
 * Description of Statement
 *
 * @author Daro
 */
class Statement extends \PDOStatement implements BindableInterface {

    protected $_db;
    protected $_paramCounter;

    public function __construct(Db $db) {
        $this->_db = $db;
        $this->_paramCounter = 0;
    }

    public function bind($params, $value = null) {
        // TODO: poprawić, zuniwersalizować
        $param = (array) $param;
        foreach ($param as $key => $value) {
            if (is_string($key)) {
                if ($key[0] !== ':')
                    $key = ':' . $key;
            } else
                $key = ++$this->_paramCounter;
            $this->bindValue($key, $value);
        }
        return $this;
    }

}