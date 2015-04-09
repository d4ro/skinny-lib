<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Skinny\Application\Components;

/**
 * Description of ComponentsTrait
 *
 * @author Daro
 */
trait ComponentsTrait {

    private $_componentsHelper;

    /**
     * Pobiera obiekt komponentu z aplikacji.
     * @param string $name
     * @return mixed
     */
    public function getComponent($name) {
        if (null === $this->_componentsHelper) {
            $this->_componentsHelper = new ComponentsAware();
        }

        return $this->_componentsHelper->getComponent($name);
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
