<?php

namespace Skinny\Application\Router\Container;

/**
 *
 * @author Daro
 */
interface ContainerInterface {

    public function getRequestUrl();

    public function getAction();

    public function getActionUrl();

    public function getActionParts();

    public function getActionDepth();

    public function getActionMatch();

    public function getArgs();

    public function getParams();

    public function setRequestUrl($requestUrl);

    public function setAction($action);

    public function setActionParts(array $actionParts);

    public function setActionMatch($actionMatch);

    public function resetArgs(array $args);

    public function setParams(array $params);

    public function resetParams(array $params = array());
}