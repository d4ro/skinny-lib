<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Skinny\Application\Components;

/**
 * Description of ComponentsAware
 *
 * @author Daro
 */
class ComponentsAware extends \Skinny\Application\ApplicationAware {

    /**
     * Pobiera obiekt komponentu z aplikacji.
     * @param string $name
     * @return mixed
     */
    public function getComponent($name) {
        $this->requireApplication('Components');

        return $this->getApplication()->getComponent($name);
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

}
