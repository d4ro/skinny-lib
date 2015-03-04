<?php

namespace Skinny\Session\Db;

use Skinny\Session\AdapterBase;

/**
 * Description of MySQL
 *
 * @author Daro
 */
class MySQL extends AdapterBase {

    protected $_db;
    protected $_savePath;
    protected $_sessionName;
    protected $_lifetime;

    public function __construct($db) {
        $this->_db = $db;
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
        if (false === $result) {
            return '';
        }

        if (!$result['valid']) {
            $this->destroy($id);
            return '';
        }

        return $result[$this->_config->table->data];
    }

    protected function getData($id) {
        $select = $this->_db->select();
        $select->from($this->_config->table->name('session', true), array(
            $this->_config->table->data('data', true),
            new \Zend_Db_Expr('IF (' . $this->_db->quoteIdentifier($this->_config->table->expires('expires', true)) . ' > now(), 1, 0) as "valid"')
        ));
        $select->where($this->_db->quoteIdentifier($this->_config->table->id('id', true)) . ' = ?', $id);
        $row = $this->_db->fetchRow($select);
        if (empty($row)) {
            return false;
        }
        return $row;
    }

    function write($id, $data) {
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
    }

    function destroy($id) {
        $result = $this->_db->delete($this->_config->table->name, array(
            $this->_db->quoteIdentifier($this->_config->table->id) . ' = ?' => $id
        ));

        return $result == 1;
    }

    function gc($maxlifetime) {
        return $this->_db->delete($this->_config->table->name, $this->_db->quoteIdentifier($this->_config->table->expires) . ' <= now()');
    }

}
