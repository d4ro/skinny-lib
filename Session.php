<?php

namespace Skinny;

use Skinny\DataObject\Store;

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

/**
 * Description of Session
 * TODO: klasa jest do przerobiernia, aby używała Db\Record, który będzie w library/Skinny
 *
 * @author Daro
 */
class Session extends DataObject\ArrayWrapper {

    /**
     * Session configuration object
     * @var Store
     */
    protected $_config;

    /**
     * Obiekt adaptera obsługującego właściwy zapis i odczyt danych sesji
     * @var Session\AdapterInterface
     */
    protected $_adapter;

    public function __construct($config, $adapter) {
        $this->_config = $config;
        $this->_adapter = $adapter;
//        $this->_adapter->setSessionConfig($this->_config);
        // zaślepka, ponieważ $_SESSION jeszcze nie istnieje
        $sessionData = array();
        parent::__construct($sessionData);
    }

    // TODO: filtry do danych
    // TODO: funkcje obsługujące automatyczny odczyt/zapis do bazy

    public function isStarted() {
        if (PHP_VERSION_ID < 50400) {
            return session_id() == '';
        }
        return session_status() == PHP_SESSION_ACTIVE;
    }

    public function start() {
        if ($this->isStarted()) {
            return false;
        }

        $this->registerCallbacks();
        $defaulName = session_name();
        session_name($this->_config->name($defaulName));

        $result = session_start();
        $this->_data = &$_SESSION;
        return $result;
    }

    protected function registerCallbacks() {
        $result = session_set_save_handler(
                array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc')
        );

        register_shutdown_function('session_write_close');

        return $result;
    }

    function open($savePath, $sessionName) {
        try {
            return $this->_adapter->open($savePath, $sessionName);
        } catch (\Exception $e) {
            header('Service Unavailable', true, 503);
            die('Session fatal error occured while opening session: ' . $e->getMessage());
        }
    }

    function close() {
        try {
            return $this->_adapter->close();
        } catch (\Exception $e) {
            header('Service Unavailable', true, 503);
            die('Session fatal error occured while closing session: ' . $e->getMessage());
        }
    }

    function read($id) {
        try {
            return $this->_adapter->read($id);
        } catch (\Exception $e) {
            header('Service Unavailable', true, 503);
            die('Session fatal error occured while reading data: ' . $e->getMessage());
        }
    }

    function write($id, $data) {
        try {
            return $this->_adapter->write($id, $data);
        } catch (\Exception $e) {
            header('Service Unavailable', true, 503);
            die('Session fatal error occured while writing data: ' . $e->getMessage());
        }
    }

    function destroy($id) {
        try {
            return $this->_adapter->destroy($id);
        } catch (\Exception $e) {
            header('Service Unavailable', true, 503);
            die('Session fatal error occured while removing data: ' . $e->getMessage());
        }
    }

    function gc($maxlifetime) {
        try {
            return $this->_adapter->gc($maxlifetime);
        } catch (\Exception $e) {
            header('Service Unavailable', true, 503);
            die('Session fatal error occured while collecting spoiled sessions: ' . $e->getMessage());
        }
    }

}
