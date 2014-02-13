<?php

namespace Skinny;

/**
 * Description of Session
 *
 * @author Daro
 */
class Session extends ArrayWrapper {

    public function __construct() {
        parent::__construct($_SESSION);
    }

    // TODO: filtry do danych
    // TODO: funkcje obsługujące automatyczny odczyt/zapis do bazy
}
