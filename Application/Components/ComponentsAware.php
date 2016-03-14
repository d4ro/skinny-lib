<?php

namespace Skinny\Application\Components;

class ComponentsAware {

    /**
     * 
     * @var \Skinny\Application\Components
     */
    private static $_components;

    /**
     * Pobiera obiekt komponentu z aplikacji.
     * @param string $name
     * @return mixed
     */
    public function getComponent($name) {
        return self::$_components->getComponent($name);
    }

    public function getConfig($key = null) {
        return self::$_components->getConfig($key);
    }

    /**
     * Nieistniejąca właściwość - pobranie komponentu aplikacji
     * np. $this->view->... odwołuje się do komponentu "view".
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->getComponent($name);
    }

    final static public function setComponents(\Skinny\Application\Components $components) {
        \Skinny\Exception::throwIf(null !== self::$_components, new \Skinny\Application\ApplicationException('Cannot set Components object to Components aware class. Components has been already set.'));
        self::$_components = $components;
    }

}
