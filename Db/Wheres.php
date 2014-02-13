<?php

namespace Skinny\Db;

/**
 * Description of Wheres
 *
 * @author Daro
 */
class Wheres extends Assemblable implements BindableInterface {

    const T_OR = 0;
    const T_AND = 1;

    protected $_db;
    protected $_string;
    protected $_type;
    protected $_segments;

    /**
     * new Wheres(Wheres::T_AND, array('col=5'));
     * new Wheres(Wheres::T_AND, array('col=5', 'col=:val'));
     * new Wheres(Wheres::T_AND, array('col=?'=>'val'));
     * new Wheres(Wheres::T_AND, array('col=:val'));
     * new Wheres(Wheres::T_AND, array('col=:val', 'col=?'=>'val'));
     * new Wheres(Wheres::T_AND, array('col=:val', array('col=?' => 'val')));
     * @param type $type
     * @param type $segments
     */
    public function __construct($type, array $segments = null) {
        $this->_type = $type;
        $this->_segments = (array) $segments;
    }

    public function bind($params, $value = null) {
        // TODO: binduje parametry w segmentach i wewnątrz nich (rekurencja)
    }

    public function add($segments) {
        $segments_all = func_get_args();
        if (count($segments_all) == 1)
            $segments_all = (array) $segments;

        while ($segments_all) {
            $segment = array_shift($segments_all);
            if (is_array($segment))
                $this->add($segment);
            else
                $this->_segments[] = $segment;
        }
    }

    protected function _assemble() {
        // TODO: zwracanie połączonego where'a
    }

}