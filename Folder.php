<?php

namespace Skinny;

/**
 * Klasa reprezentująca folder w systemie plików systemu operacyjnego.
 * Umożliwia operacje na zawartości folderu takie, jak odczyt,
 * oparcje ogólne takie, jak usuwanie, przenoszenie, kopiowanie oraz
 * dostarcza informacje o folderze takie, jak wielkość, ścieżka, itp.
 */
class Folder {

    /**
     * Deskryptor otwartego pliku; pozostaje null, gdy plik nie został otwarty
     * @var resource
     */
    protected $_descriptor;

    /**
     * Ścieżka do pliku
     * @var string
     */
    protected $_path;

    public function __construct($path) {
        $this->_path = $path;
    }

    public function open() {
        $this->close();

        if (($f = opendir($this->_path))) {
            $this->_descriptor = $f;
            return true;
        } else {
            return false;
        }
    }

    public function close() {
        if ($this->isOpened()) {
            closedir($this->_descriptor);
        }

        $this->_descriptor = null;
    }

    public function isOpened() {
        return null !== $this->_descriptor;
    }

    public function isFile() {
        return is_file($this->_path);
    }

    public function size() {
        return filesize($this->_path);
    }

    public function delete() {
        unlink($this->_path);
    }

    public function exists() {
        return is_dir($this->_path);
    }

    public function chmod($val) {
        return chmod($this->_path, $val);
    }

    public function lock($mode) {
        return flock($this->_descriptor, $mode);
    }

    public function unlock() {
        return $this->lock(LOCK_UN);
    }

    public function isReadable() {
        return is_readable($this->_path);
    }

    public function copyTo($path) {
        return copy($this->_path, $path);
    }

    /**
     * Pobiera zawartość folderu. Zwraca array obiektów File lub Folder.
     * 
     * @return array
     * @throws IOException
     */
    public function getContent() {
        $close = !$this->isOpened();
        if ($close && !$this->open()) {
            throw new IOException('Could not open folder "' . $this->_path . '".');
        }

        $files = [];
        while (($file  = readdir($this->_descriptor)) !== false) {
            $filename = Path::combine($this->_path, $file);
            if (filetype($filename) == 'dir') {
                $files[] = new Folder($filename);
            } else {
                $files[] = new File($filename);
            }
        }

        if ($close) {
            $this->close();
        }

        return $files;
    }

}
