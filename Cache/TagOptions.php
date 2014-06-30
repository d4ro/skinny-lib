<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Skinny\Cache;

/**
 * Description of TagOptions
 *
 * @author Daro
 */
abstract class TagOptions extends \Skinny\EnumBase {

    const ALL = 0;
    const ANY = 1;
    const STRICT = 2;

}
