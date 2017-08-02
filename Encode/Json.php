<?php

namespace Skinny\Encode;

/**
 * Description of Json
 *
 * @author Daro
 */
class Json implements EncodeInterface {

    public static function decode($string) {
        return json_decode($string, true);
    }

    public static function encode($data) {
        return json_encode($data);
    }

}
