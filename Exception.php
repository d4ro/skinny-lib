<?php

namespace Skinny;

/**
 * Description of SkinnyException
 *
 * @author Daro
 */
class Exception extends \Exception {

    public static function raise($exception) {
        throw $exception;
    }

    public static function raiseIf($exception, $condition) {
        if ($condition) {
            throw $exception;
        }
    }

}
