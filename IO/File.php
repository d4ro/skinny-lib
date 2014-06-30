<?php

namespace Skinny\IO;

/**
 * Description of File
 *
 * @author Daro
 */
class File {

    protected $_descriptor;
    protected $_mode;
    protected $_path;

    public function __construct($path) {
        $this->_path = $path;
    }

    public function open($mode) {
        $this->close();

        if (($f = @fopen($this->_path, $mode))) {
            $this->_descriptor = $f;
            $this->_mode = $mode;
            return true;
        }
        else
            return false;
    }

    public function close() {
        if ($this->isOpened())
            fclose($this->_descriptor);

        $this->_descriptor = null;
        $this->_mode = null;
    }

    public function isOpened() {
        return null !== $this->_descriptor;
    }

    public function getMode() {
        return $this->_mode;
    }

    public function write($content) {
        $close = !$this->isOpened();
        if ($close && !$this->open('wb'))
            throw new IOException('Could not open file "' . $this->_path . '" to write.');

        fwrite($this->_descriptor, $content);

        if ($close)
            $this->close();
    }

    public function read() {
        
    }

    public function delete() {
        
    }

    public function exists() {
        
    }

    public function create() {
        touch($this->_path);
    }

    public function append() {
        
    }

}