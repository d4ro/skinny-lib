<?php

namespace Skinny\Session\Db;

use Skinny\Session\AdapterBase;

/**
 * Description of MySQL
 *
 * @author Daro
 */
class MySQL extends AdapterBase {

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $_db;
    protected $_savePath;
    protected $_sessionName;
    protected $_lifetime;

    public function __construct($db) {
        $this->_db = $db;
    }

    public function setSessionConfig($config) {
        parent::setSessionConfig($config);

        $this->_lifetime = $this->_config->lifetime(get_cfg_var("session.gc_maxlifetime"), true);
    }

    function open($savePath, $sessionName) {
        $this->_sessionName = $sessionName;
        $this->_savePath    = $savePath;

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
        $select = $this->getSelect($id);
        $row    = $this->_db->fetchRow($select);
        if (empty($row)) {
            return false;
        }
        return $row;
    }

    protected function getSelect($id) {
        $select = $this->_db->select();
        $select->from($this->_config->table->name('session', true),
            array(
            $this->_config->table->data('data', true),
            new \Zend_Db_Expr('IF (' . $this->_db->quoteIdentifier($this->_config->table->expires('expires', true)) . ' > now(), 1, 0) as "valid"')
        ));
        $select->where($this->_db->quoteIdentifier($this->_config->table->id('id', true)) . ' = ?', $id);
        return $select;
    }

    function write($id, $data) {
        $expires = new \Zend_Db_Expr('DATE_ADD(NOW(), INTERVAL ' . $this->_lifetime . ' SECOND)');

        $result = $this->getData($id);
        if (false === $result) {
            $sql = 'REPLACE INTO %s (%s, %s, %s) VALUES (%s, %s, %s);';
            $sql = sprintf($sql, $this->_db->quoteIdentifier($this->_config->table->name),
                $this->_db->quoteIdentifier($this->_config->table->id),
                $this->_db->quoteIdentifier($this->_config->table->expires),
                $this->_db->quoteIdentifier($this->_config->table->data), $this->_db->quote($id),
                $this->_db->quote($expires), $this->_db->quote($data));
            $this->_db->query($sql);
//            $this->_db->inset($this->_config->table->name, array(
//                $this->_config->table->id => $id,
//                $this->_config->table->expires => $expires,
//                $this->_config->table->data => $data
//            ));
        } else {
            $this->_db->update($this->_config->table->name,
                array(
                $this->_config->table->expires => $expires,
                $this->_config->table->data    => $data
                ),
                array(
                $this->_db->quoteIdentifier($this->_config->table->id) . ' = ?' => $id
            ));
        }

        return true;
    }

    function destroy($id) {
        $result = $this->_db->delete($this->_config->table->name,
            array(
            $this->_db->quoteIdentifier($this->_config->table->id) . ' = ?' => $id
        ));

        return $result == 1;
    }

    function gc($maxlifetime) {
        return $this->_db->delete($this->_config->table->name,
                $this->_db->quoteIdentifier($this->_config->table->expires) . ' <= now()');
    }

}
