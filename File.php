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
            $this->_mode       = $mode;
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
        $this->_mode       = null;
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
            $mime  = @finfo_open($const);

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
        header('Content-Type: ' . $this->getMimeType());
        header('Content-Disposition: attachment; filename="' . ($outputFileName ? $outputFileName : basename($this->getPath())) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($this->getPath()));
        readfile($this->getPath());
        exit;
    }

    /**
     * Przenosi istniejący plik do podanej lokalizacji, gdzie nazwa pliku jest wygenerowanym,
     * unikalnym ciągiem znaków zakodowanym w sha1. 
     * 
     * UWAGA - nazwa pliku jest podzielona co dwa znaki
     * i w katalogu docelowym zostanie utworzony ciąg katalogów będących kolejnymi cząstkami wygenerowanej
     * nazwy pliku. Ostatnie dwa znaki będą faktyczną nazwą pliku.
     * 
     * Np.: Dla pliku o nazwie "f10e2821bbbea527ea02200352313bc059445190" plik będzie miał nazwę "90"
     * i będzie się znajdował w katalogu (zakładając że $targetPath to: "../files"):
     * "../files/f1/0e/28/21/bb/be/a5/27/ea/02/20/03/52/31/3b/c0/59/44/51/90" <-- za ostatnim slashem nazwa pliku.
     * 
     * @param string $sourcePath Ścieżka pliku do przeniesienia
     * @param string $targetPath Ścieżka docelowa
     * @return string Zwraca wygenerowaną nazwę pliku
     * @throws Exception
     */
    public function moveUniqueSha1(string $targetPath) {
        // walidacja istnienia ścieżki docelowej
        if (!file_exists($targetPath)) {
            throw new Exception('Target path does not exist');
        }

        // sprawdzenie istnienia pliku
        if (!$this->exists()) {
            throw new Exception('File does not exist');
        }

        // wygenerowanie unikalnej nazwy pliku oraz ścieżki w podanej lokalizacji
        list($filename, $path) = static::getTargetsUniqueHashPath($targetPath);

        // rekurencyjne utworzenie ściezki do pliku (pomijając nazwę pliku czyli ostatnie dwa znaki oraz separator)
        if (!\Skinny\Path::create(($dir = substr($path, 0, -3)))) {
            throw new Exception("Couldn't create direcotry '$dir'");
        }

        // przeniesienie pliku do lokalizacji docelowej
        if (!($this->moveTo($path))) {
            throw new Exception("Couldn't move file to '$path'");
        }

        return $filename;
    }

    /**
     * Generuje dla podanej lokalizacji unikalną nazwę dla pliku w postaci hasha sha1 i zwraca
     * jego ścieżkę dostępu.
     * 
     * Nazwa sha1 podzielona jest co drugi znak 
     * (np. $targetPath/f1/0e/28/21/bb/be/a5/27/ea/02/20/03/52/31/3b/c0/59/44/51/90).
     * 
     * @param string $targetPath Ścieżka w której chcemy wygenerować unikalną nazwę
     * @return array Zwraca dwuelementową tablicę zawierającą kolejno wygenerowaną nazwę (sha1) oraz pełną ścieżkę
     * @throws Exception
     */
    static public function getTargetsUniqueHashPath(string $targetPath) {
        // walidacja istnienia ścieżki docelowej
        if (!file_exists($targetPath)) {
            throw new Exception('Target path does not exist');
        }

        // generowanie unikalnej nazwy pliku
        while (file_exists(
            ($path = \Skinny\Path::combine(
                $targetPath, implode(str_split(($hash = sha1(uniqid())), 2), DIRECTORY_SEPARATOR)
            ))
        ));

        return [$hash, $path];
    }

}
