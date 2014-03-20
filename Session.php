<?php

namespace Skinny;

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
class Session extends ArrayWrapper {

    protected $_config;

    /**
     * Obiekt połączanie z bazą danych
     * @var \Zend_Db_Adapter_Abstract|Db
     */
    protected $_db;

    /**
     * Obiekt Memcached
     * @var \Memcached|Cache\Memcached
     */
    protected $_memcached;
    protected $_savePath;
    protected $_sessionName;
    protected $_lifetime;

    public function __construct($config, $db = null, $memcached = null) {
        $this->_config = $config;
        // TODO: obsługa configa
        $this->_db = $db;
        $this->_memcached = $memcached;

        // zaślepka, ponieważ $_SESSION jeszcze nie istnieje
        $x = array();
        parent::__construct($x);
    }

    // TODO: filtry do danych
    // TODO: funkcje obsługujące automatyczny odczyt/zapis do bazy

    public function isStarted() {
        if (PHP_VERSION_ID < 50400)
            return session_id() == '';
        return session_status() == PHP_SESSION_ACTIVE;
    }

    public function start() {
        if ($this->isStarted())
            return false;

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
        $this->_sessionName = $sessionName;
        $this->_savePath = $savePath;
        $this->_lifetime = $this->_config->lifetime(get_cfg_var("session.gc_maxlifetime"), true);

        return true;
    }

    function close() {
        // Nie trzeba nic robić

        return true;
    }

    function read($id) {
        $result = $this->getData($id);
        if (false === $result)
            return '';

        if (!$result['valid']) {
            $this->destroy($id);
            return '';
        }

        return $result[$this->_config->table->data];
    }

    protected function getData($id) {
        try {
            $select = $this->_db->select();
            $select->from($this->_config->table->name('session', true), array(
                $this->_config->table->data('data', true),
                new \Zend_Db_Expr('IF (' . $this->_db->quoteIdentifier($this->_config->table->expires('expires', true)) . ' > now(), 1, 0) as "valid"')
            ));
            $select->where($this->_db->quoteIdentifier($this->_config->table->id('id', true)) . ' = ?', $id);
            $row = $this->_db->fetchRow($select);
            if(empty($row))
                return false;
            return $row;
        } catch (\Exception $e) {
            die('Session fatal error occured: ' . $e->getMessage());
        }
    }

    function write($id, $data) {
        try {
            $expires = new \Zend_Db_Expr('DATE_ADD(NOW(), INTERVAL ' . $this->_lifetime . ' SECOND)');

            $result = $this->getData($id);
            if (false === $result) {
                $this->_db->insert($this->_config->table->name, array(
                    $this->_config->table->id => $id,
                    $this->_config->table->expires => $expires,
                    $this->_config->table->data => $data
                ));
            } else {
                $this->_db->update($this->_config->table->name, array(
                    $this->_config->table->expires => $expires,
                    $this->_config->table->data => $data
                        ), array(
                    $this->_db->quoteIdentifier($this->_config->table->id) . ' = ?' => $id
                ));
            }

            return true;
        } catch (\Exception $e) {
            die('Session fatal error occured: ' . $e->getMessage());
        }
    }

    function destroy($id) {
        try {
            $result = $this->_db->delete($this->_config->table->name, array(
                $this->_db->quoteIdentifier($this->_config->table->id) . ' = ?' => $id
            ));

            return $result == 1;
        } catch (\Exception $e) {
            die('Session fatal error occured: ' . $e->getMessage());
        }
    }

    function gc($maxlifetime) {
        try {
            return $this->_db->delete($this->_config->table->name, $this->_db->quoteIdentifier($this->_config->table->expires) . ' <= now()');
        } catch (\Exception $e) {
            die('Session fatal error occured: ' . $e->getMessage());
        }
    }

}
