<?php

namespace Skinny\Application\Router;

/**
 *
 * @author Daro
 */
interface RouterInterface {

    function getRoute($path, Container\ContainerInterface $container = null);
}
