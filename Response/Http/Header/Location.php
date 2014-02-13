<?php

namespace Skinny\Response\Http\Header;

/**
 * Description of Location
 *
 * @author Daro
 */
class Location extends HeaderBase {

    public function __construct($url, $code = 302) {
        $this->_name = 'Location';
        $this->_value = $url;
        $this->_code = $code;
    }

}
