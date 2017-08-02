<?php

namespace Skinny\Db;

use Skinny\Db;

/**
 * Reprezentuje dające się złożyć (assemblable) wyrażenie bazodanowe będące zapytaniem SQL lub jego fragmentem.
 *
 * @author Daro
 */
abstract class Assemblable extends DatabaseAware implements AssemblableInterface {

    protected $_assembled;

    public function assemble(Db $db = null) {
        // TODO: walidacja $db
        if (null !== $db)
            $this->_db        = $db;
        $this->_assembled = $this->_assemble();
    }

    protected abstract function _assemble();

    public function __toString() {
        if (!isset($this->_assembled))
            trigger_error('Expression is not assembled.');

        return $this->_assembled;
    }

}
