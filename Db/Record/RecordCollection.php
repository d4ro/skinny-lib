<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Skinny\Db\Record;

/**
 * Description of RecordCollection
 *
 * @author Daro
 */
class RecordCollection extends \Skinny\ArrayWrapper {

    public function __construct(array $collection = array(), $checkType = true) {
        if ($checkType) {
            $this->checkArrayType($collection, true);
        }
        
        parent::__construct($collection);
    }

    protected function checkArrayType(array $collection, $throw = false) {
        $exception = new InvalidRecordException('Record Collection contains non Record elements.');
        foreach ($collection as $element) {
            if (!($element instanceof RecordBase)) {
                \Skinny\Exception::throwIf($throw == true, $exception);
                return false;
            }
        }
        return true;
    }

    public function getIds() {
        return $this->call('getId');
    }

    public function save() {
        // TODO: optymalizacja - pobranie zapytań do bazy i wykonanie ich w jednym query
        return $this->call('save');
    }
    
    public function delete() {
        $ids = $this->getIds();
        
    }

    public function apply($property, $value) {
        foreach ($this->_data as $record) {
            $record->$property = $value;
        }
    }

    public function call($method, array $params = array()) {
        $result = array();
        foreach ($this->_data as $record) {
            $result[$record->getId()] = call_user_method_array($method, $record, $params);
        }
        return $result;
    }

}
