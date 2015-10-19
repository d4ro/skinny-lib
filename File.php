<?php

namespace Skinny;

/**
 * Klasa reprezentująca plik w systemie plików systemu operacyjnego.
 * Umożliwia operacje na zawartości pliku takie, jak odczyt, zapis,
 * oparcje ogólne takie, jak usuwanie, przenoszenie, kopiowanie oraz
 * dostarcza informacje o pliku takie, jak rozmiar, ścieżka, itp.
 */
class File {

    /**
     * Deskryptor otwartego pliku; pozostaje null, gdy plik nie został otwarty
     * @var resource
     */
    protected $_descriptor;

    /**
     * Tryb dostępu do pliku; pozostaje null, gdy plik nie został otwarty
     * @var string
     */
    protected $_mode;

    /**
     * Ścieżka do pliku
     * @var string
     */
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
        } else {
            return false;
        }
    }

    public function close() {
        if ($this->isOpened()) {
            fclose($this->_descriptor);
        }

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
        if ($close && !$this->open('wb')) {
            throw new IOException('Could not open file "' . $this->_path . '" to write.');
        }

        if ($this->lock(LOCK_EX)) {
            fwrite($this->_descriptor, $content);
            $this->unlock();
        }

        if ($close) {
            $this->close();
        }
    }

    public function read($length = null) {
        // TODO
    }

    public function delete() {
        unlink($this->_path);
    }

    public function exists() {
        return file_exists($this->_path);
    }

    public function create() {
        touch($this->_path);
    }

    public function append($content) {
        $close = !$this->isOpened();
        if ($close && !$this->open('ab')) {
            throw new IOException('Could not open file "' . $this->_path . '" to append.');
        }

        if ($this->lock(LOCK_EX)) {
            fwrite($this->_descriptor, $content);
            $this->unlock();
        }

        if ($close) {
            $this->close();
        }
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

}
