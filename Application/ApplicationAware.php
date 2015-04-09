<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Skinny\Application;

/**
 * Description of ApplicationAware
 *
 * @author Daro
 */
abstract class ApplicationAware {

    /**
     * 
     * @var Application
     */
    private static $_application;

    final protected function requireApplication($objectName = 'Application') {
        \Skinny\Exception::throwIf(null === self::$_application, new ApplicationException("Cannot invoke $objectName object while Application instance is not set in current Application Aware Object."));
    }

    final protected function getApplication() {
        return self::$_application;
    }

    final static public function setApplication(\Skinny\Application $application) {
        \Skinny\Exception::throwIf(null !== self::$_application, new ApplicationException('Cannot set Application object to Application Aware Classes. Application has been already set.'));
        self::$_application = $application;
    }

}
