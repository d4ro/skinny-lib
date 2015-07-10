<?php

namespace Skinny\View;

class Files implements \IteratorAggregate {
    
    protected $_filesPath = null;
    protected $_extension = null;
    protected $_baseUrl = null;
    
    protected $_items = [];
    protected $_index = [];
    
    /**
     * Umożliwia iterowanie bezpośrednio po elementach tablicy $_items.
     * 
     * @return ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->_items);
    }
    
    /**
     * Konstruktor kolekcji plików.
     * 
     * @param string $path Ścieżka do katalogu głównego z plikami
     * @param string $extension Rozszerzenie plików (np. ".js")
     * @throws Exception
     */
    public function __construct($baseUrl, $path, $extension) {
        if(empty($baseUrl) || !is_string($baseUrl)) {
            throw new Exception('Argument $baseUrl (' . $baseUrl . ') is invalid');
        }
        if(empty($path) || !is_string($path)) {
            throw new Exception('Argument $path (' . $path . ') is invalid');
        }
        if(empty($extension) || !is_string($extension)) {
            throw new Exception('Argument $extension (' . $extension . ') is invalid');
        }
        
        $this->_filesPath = $path;
        $this->_extension = $extension;
        $this->_baseUrl = $baseUrl;
    }
    
    
    /**
     * Dodaje plik do kolekcji.
     * 
     * @param string $file
     * @param boolean $checkFileExistance Wymusza sprawdzenie istnienia pliku
     * @return \Skinny\View\Files
     * @throws Exception
     */
    public function add($file, $checkFileExistance = false) {
        $filePath = $this->_getFilePath($file);
        
        if($checkFileExistance) {
            if(!file_exists($filePath)) {
                throw new Exception("File $filePath doesn't exist");
            }
        }
        
        $this->_items[] = $filePath;
        $this->_index[$file] = &$this->_items[count($this->_items) - 1];
        
        return $this;
    }
    
    /**
     * Dodaje plik na początku.
     * @param string $file
     * @param voolean $checkFileExistance
     * @return \Skinny\View\Files
     * @throws Exception
     */
    public function addFirst($file, $checkFileExistance = false) {
        $filePath = $this->_getFilePath($file);
        
        if($checkFileExistance) {
            if(!file_exists($filePath)) {
                throw new Exception("File $filePath doesn't exist");
            }
        }
        
        array_unshift($this->_items, $filePath);
        $this->_index[$file] = &$this->_items[0];
        
        return $this;
    }
    
    /**
     * Konfiguruje i zwraca pełną ścieżkę (absolutną lub url) do wybranego pliku.
     * 
     * @param string $file
     * @return string
     */
    protected function _getFilePath($file) {
        if(!\Skinny\Path::isAbsolute($file) && !\Skinny\Url::hasProtocol($file)) {
            // należy dokleić ścieżkę do plików JS w momencie gdy adres pliku nie jest
            // absolutny i nie jest też urlem
            $file = \Skinny\Path::combine($this->_baseUrl, $this->_filesPath, $file);
        }
        
        return $file . $this->_extension;
    }
    
    /**
     * Usuwa wybrany plik z kolekcji.
     * 
     * @param string $file
     * @return \Skinny\View\Files
     */
    public function remove($file) {
        if(isset($this->_index[$file])) {
            unset($this->_index[$file]);
        }
        
        return $this;
    }
}
