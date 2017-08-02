<?php

namespace Skinny\Application\Response;

/**
 * Description of ResponseBase
 *
 * @author Daro
 */
abstract class ResponseBase implements ResponseInterface {

    protected $_body;
    protected $_code;
    protected $_headers = array();

    abstract public function respond();

    public function setBody($body) {
        $this->_body = $body;
    }

    public function setHeader($name, $value, $code = null) {
        if (null !== $code) {
            $this->setCode($code);
        }

        $this->_headers[$name] = $value;
    }

    public function setCode($code) {
        $this->_code = $code;
    }

}
