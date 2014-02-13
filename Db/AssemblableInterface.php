<?php

namespace Skinny\Db;

/**
 * Description of AssemblableInterface
 *
 * @author Daro
 */
interface AssemblableInterface {

    public function assemble(Db $db = null);
}
