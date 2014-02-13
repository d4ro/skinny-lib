<?php

namespace Skinny\Db;

/**
 *
 * @author Daro
 */
interface BindableInterface {

    public function bind($params, $value = null);
}

