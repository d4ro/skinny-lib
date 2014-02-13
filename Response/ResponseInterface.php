<?php

namespace Skinny\Response;

/**
 * Description of ResponseInterface
 *
 * @author Daro
 */
interface ResponseInterface {

    public function setHeader($name, $value, $code = null);

    public function setCode($code);

    public function setBody($body);

    public function respond();
}
