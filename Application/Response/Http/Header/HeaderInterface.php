<?php

namespace Skinny\Application\Response\Http\Header;

/**
 * Description of HeaderInterface
 *
 * @author Daro
 */
interface HeaderInterface {

    public function getName();

    public function getValue();

    public function getCode();
    
    public function toString();
}
