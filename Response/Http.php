<?php

namespace Skinny\Response;

/**
 * Description of Http
 *
 * @author Daro
 */
class Http extends ResponseBase {

    public function __construct() {
        $this->_code = 200;
    }

    public function respond() {
        $this->sendHeaders();
        $this->sendBody();
    }

    /**
     * Ustawia nagłówek według obiektu Http\Header
     * @param Http\Header\HeaderInterface $headerObj
     */
    public function header($headerObj) {
        $this->setHeader($headerObj->getName(), $headerObj->getValue(), $headerObj->getCode());
    }

    protected function sendHeaders() {
        header('HTTP/1.1 ' . $this->_code);
        foreach ($this->_headers as $name => $value)
            header($name . ': ' . $value);
    }

    protected function sendBody() {
        echo $this->_body;
    }

}

