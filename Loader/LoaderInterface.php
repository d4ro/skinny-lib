<?php

namespace Skinny\Loader;

/**
 * Description of LoaderInterface
 *
 * @author Daro
 */
interface LoaderInterface {

    public function isRegistered();

    public function register();

    public function load($className);
}
