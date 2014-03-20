<?php

namespace Skinny\Encode;

/**
 *
 * @author Daro
 */
interface EncodeInterface {

    public static function encode($data);

    public static function decode($string);
}

