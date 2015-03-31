<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Skinny;

/**
 * Description of ObjectHelper
 *
 * @author Daro
 */
class ObjectHelper {

    public static function getPublicProperties($object, $excludeClosures = true) {
        $props = get_object_vars($object);
        if ($excludeClosures) {
            foreach ($props as $key => $value) {
                if ($value instanceof \Closure) {
                    unset($props[$key]);
                }
            }
        }
        return$props;
    }

}
