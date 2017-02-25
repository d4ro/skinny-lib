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

    public function isFile() {
        return is_file($this->_path);
    }

    public function getMode() {
        return $this->_mode;
    }

    /**
     * Zwraca ustawioną ścieżkę pliku.
     * 
     * @return string
     */
    public function getPath() {
        return $this->_path;
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
        $close = !$this->isOpened();
        if ($close && !$this->open('r')) {
            throw new IOException('Could not open file "' . $this->_path . '" to read.');
        }

        $content = fread($this->_descriptor, $this->size());

        if ($close) {
            $this->close();
        }

        return $content;
    }

    public function size() {
        return filesize($this->_path);
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

    public function isHidden() {
        return basename($this->_path)[0] == '.';
    }

    public function getName() {
        return basename($this->_path);
    }

    public function getExtension() {
        $path_parts = pathinfo($this->_path);

//echo $path_parts['dirname'], "\n";
//echo $path_parts['basename'], "\n";
        return $path_parts['extension'];
//echo $path_parts['filename'], "\n"; // od PHP 5.2.0
//        return basename($this->_path);
    }

    /**
     * Kopiuje plik do podanej ścieżki.
     * 
     * @param string $path
     * @return boolean
     */
    public function copyTo($path) {
        return copy($this->_path, $path);
    }

    /**
     * Przenosi plik do podanej ścieżki.
     * 
     * @param string $path
     * @return boolean
     */
    public function moveTo($path) {
        return rename($this->_path, $path);
    }

    /**
     * Zwraca MIME_TYPE pliku.
     * 
     * @return string
     */
    public function getMimeType() {
        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $mime = @finfo_open($const);

            if (!empty($mime))
                $mimeType = finfo_file($mime, $this->_path);

            unset($mime);
        }

        if (empty($mimeType) && (function_exists('mime_content_type') && ini_get('mime_magic.magicfile'))) {
            $mimeType = mime_content_type($this->_path);
        }

        if (empty($mimeType)) {
            $mimeType = 'application/octet-stream';
        }

        return $mimeType;
    }

    public function rename($newName) {
        rename($this->_path, $newName);
        $this->_path = realpath($newName);
    }

    /**
     * Czyta plik i ustawia go jako załącznik do pobrania.
     */
    public function output($outputFileName = null) {
        if (!file_exists($this->getPath())) {
            throw new Exception('File does not exist.');
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . ($outputFileName ? $outputFileName : basename($this->getPath())) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($this->getPath()));
        readfile($this->getPath());
        exit;
    }

}
