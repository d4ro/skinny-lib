<?php

namespace Skinny\Encode;

/**
 * Description of Serialize
 *
 * @author Daro
 */
class Serialize implements EncodeInterface {

    public static function decode($string) {
        return unserialize($string);
    }

    public static function encode($data) {
        return serialize($data);
    }

}
