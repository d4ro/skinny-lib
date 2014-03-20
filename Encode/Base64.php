<?php

namespace Skinny\Encode;

/**
 * Description of Base64
 *
 * @author Daro
 */
class Base64 implements EncodeInterface {

    public static function encode($data) {
        return base64_encode($data);
    }

    public static function decode($string) {
        return base64_decode($string, false);
    }

}