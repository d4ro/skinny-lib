<?php

namespace Skinny\View;

class Files implements \IteratorAggregate {

    /**
     * Ścieżka do katalogu z plikami tego typu.
     * @var string
     */
    protected $_filesPath;

    /**
     * Rozszerzenie plików tego typu.
     * @var string
     */
    protected $_extension;

    /**
     * Bazowy url aplikacji - potrzebny przy metodzie pobierającej publiczny url pliku.
     * @var string 
     */
    protected $_baseUrl;

    /**
     * Ścieżka aplikacji (katalog public)
     * @var string
     */
    protected $_applicationPath;

    /**
     * Tablica asocjacyjna dodanych plików.
     * @var array
     */
    protected $_items = [];

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
     * @param string $baseUrl           Bazowy url aplikacji
     * @param string $applicationPath   Ścieżka publiczna aplikacji
     * @param string $filesPath         Ścieżka do katalogu głównego z plikami
     * @param string $extension         Rozszerzenie plików (np. ".js")
     * @throws Exception
     * 
     * @todo Czy należy sprawdzać istnienie ścieżek? applicationPath i/lub filesPAth?
     */
    public function __construct($baseUrl, $applicationPath, $filesPath, $extension) {
        if (!is_string($baseUrl)) {
            throw new Exception('Argument $baseUrl (' . $baseUrl . ') is invalid');
        }
        if (!is_string($applicationPath)) {
            throw new Exception('Argument $applicationPath (' . $applicationPath . ') is invalid');
        }
        if (!is_string($filesPath)) {
            throw new Exception('Argument $path (' . $filesPath . ') is invalid');
        }
        if (!is_string($extension)) {
            throw new Exception('Argument $extension (' . $extension . ') is invalid');
        }

        $this->_baseUrl         = $baseUrl;
        $this->_applicationPath = $applicationPath;
        $this->_filesPath       = $filesPath;
        $this->_extension       = $extension;
    }

    /**
     * Dodaje plik do kolekcji.
     * Plik może być adresem url - wtedy od razu dodawany jest do kolekcji.
     * Może być również ścieżką bezwzględną - wtedy ścieżka tworzona jest od katalogu
     * "public" aplikacji (np. "file" = "/js/file")
     * 
     * @param string    $file
     * @param boolean   $requireFileExistance Wymusza sprawdzenie istnienia pliku
     *                                      oraz zrzucenie exceptiona gdy nie istnieje
     * @return \Skinny\View\Files
     * @throws Exception
     */
    public function add($file, $requireFileExistance = true) {
        if (!$this->fileExists(($filePath = $this->_getFilePath($file)))) {
            if ($requireFileExistance) {
                throw new Exception("File '$filePath' doesn't exist.");
            }
        } else {
            $url                = $this->_getFileUrl($file);
            $this->_items[$url] = $url;
        }

        return $this;
    }

    /**
     * Dodaje plik na początku.
     * @param string $file
     * @param voolean $requireFileExistance
     * @return \Skinny\View\Files
     * @throws Exception
     */
    public function addFirst($file, $requireFileExistance = true) {
        if (!$this->fileExists(($filePath = $this->_getFilePath($file)))) {
            if ($requireFileExistance) {
                throw new Exception("File '$filePath' doesn't exist.");
            }
        } else {
            // dodanie elementu na początek tablicy asocjacyjnej
            $url          = $this->_getFileUrl($file);
            $this->_items = [$url => $url] + $this->_items;
        }

        return $this;
    }

    /**
     * Sprawdza istnienie pliku pod podaną ścieżką.
     * UWAGA - w przypadku adresu URL funkcja zwraca TRUE.
     * 
     * @param string $filePath
     * @return boolean
     */
    public function fileExists($filePath) {
        if (\Skinny\Url::hasProtocol($filePath)) {
            return true; // TODO ewentualne sprawdzanie istnienia pliku pod adresem URL
        } else {
            return file_exists($filePath);
        }
    }

    /**
     * Konfiguruje i zwraca ścieżkę (względną lub url) do wybranego pliku.
     * 
     * @param string $file
     * @return string|false
     */
    protected function _getFilePath($file) {
        if (\Skinny\Url::hasProtocol($file)) {
            return $file;
        } else {
            // jeżeli ścieżka nie jest absolutna to należy do ścieżki dodać
            // na początku lokalizację katalogu z plikami tego typu
            if (!\Skinny\Path::isAbsolute($file)) {
                $file = \Skinny\Path::combine($this->_applicationPath, $this->_filesPath, $file);
            } else {
                // Jeżeli ścieżka jest absolutna należy zwrócić ścieżkę liczoną od
                // katalogu publicznego aplikacji
                $file = $this->_applicationPath . $file;
            }
            // dołączenie odpowiedniego rozszerzenia jeśli nie ustawione
            if (!$this->hasExtensionAlready($file)) {
                $file .= $this->_extension;
            }

            return $file;
        }
    }

    /**
     * Konfiguruje i zwraca url względny pliku lub wartość argumentu $file jeżeli jest URL'em.
     * 
     * @param string $file
     * @return string
     */
    protected function _getFileUrl($file) {
        if (\Skinny\Url::hasProtocol($file)) {
            return $file;
        } else {
            // jeżeli nie podano ścieżki bezwzględnej należy ją dokleić
            if (!\Skinny\Path::isAbsolute($file)) {
                $file = \Skinny\Path::combine($this->_baseUrl, $this->_filesPath, $file);
            }

            // dołączenie odpowiedniego rozszerzenia jeśli nie ustawione
            if (!$this->hasExtensionAlready($file)) {
                $file .= $this->_extension;
            }

            return $file;
        }
    }

    /**
     * Usuwa wybrany plik z kolekcji.
     * 
     * @param string $file
     * @return \Skinny\View\Files
     */
    public function remove($file) {
        $url = $this->_getFileUrl($file);
        if (isset($this->_items[$url])) {
            unset($this->_items[$url]);
        }
        return $this;
    }

    /**
     * Sprawdza czy podana nazwa pliku ma już w sobie żądane rozszerzenie.
     * @param string $file
     * @return boolean
     */
    public function hasExtensionAlready($file) {
        return false !== strrpos($file, $this->_extension);
    }

    public function clear() {
        $this->_items = [];
    }

}
