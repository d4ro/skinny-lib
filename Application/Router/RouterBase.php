<?php

namespace Skinny\Application\Router;

use Skinny\IOException;
use Skinny\Path;

abstract class RouterBase implements RouterInterface {

    /**
     * Ścieżka do katalogu z plikami zawartości aplikacji
     * @var string
     */
    protected $_contentPath;

    /**
     * Ścieżka do katalogu z cache aplikacji
     * @var string
     */
    protected $_cachePath;

    /**
     * Ścieżka bazowa URL
     * @var string
     */
    protected $_baseUrl;

    /**
     * Konfiguracja routera
     * @var \Skinny\DataObject\Store
     */
    protected $_config;

    /**
     * Odczytuje zawartość katalogu i zwraca tablicę jego zawartości, gdzie:
     * - pliki (wyłącznie z rozszerzeniem .php, niezaczynające się od kropki "."):
     *   reprezentowane są bez rozszerzenia, pod kolejnym indeksem numerycznym
     * - katalogi (niezaczynające się od kropki "."):
     *   reprezentowane są w kluczu, gdzie wartość jest rekurencyjną zawartością katalogu
     * 
     * @param string $dirPath ścieżka do katalogu
     * @return array
     */
    protected static function _readActionsDir($dirPath) {
        $dir     = dir($dirPath);
        $actions = [];
        while ($item    = $dir->read()) {
            $path = $dirPath . DIRECTORY_SEPARATOR . $item;
            if ($item[0] == '.') {
                continue;
            }

            if (is_dir($path)) {
                $actions[$item] = self::_readActionsDir($path);
                continue;
            }

            if (substr($item, -4) == '.php') {
                $actions[] = substr($item, 0, -4);
            }
        }
        return $actions;
    }

    /**
     * Iteruje po katalogu zawartości aplikacji wyszukując pliki .php akcji.
     * Rezultat wyszukiwania zapisuje w cache.
     * 
     * @return array tablica będąca reprezentacją struktury akcji w folderze zawartości aplikacji
     * @throws IOException
     */
    protected function _resolveActions() {
        if (!is_dir($this->_contentPath)) {
            throw new IOException('Could not read application content directory.');
        }

        $actions     = self::_readActionsDir($this->_contentPath);
        $actionsPath = Path::combine($this->_cachePath, 'actions.php');

        $file = new \Skinny\File($actionsPath);
        $file->write('<?php return ' . var_export($actions, true) . ';');
        return $actions;
    }

    /**
     * Przekształca klucz tablicowy parametru na ścieżkę oddzieloną ukośnikiem "/".
     * Przykładowo: a[b][c] => a/b/c
     * 
     * @param string $key
     * @return string
     */
    protected static function _trueKey($key) {
        $start = strpos($key, '[');
        if (false === $start) {
            return $key;
        }

        $result = substr($key, 0, $start);
        do {
            $end = strpos($key, ']', $start);
            if (false === $end) {
                return $result . '/' . substr($key, $start + 1);
            }

            $result .= '/' . substr($key, $start + 1, $end - $start - 1);
        } while ($start = strpos($key, '[', $end));

        return $result;
    }

    /**
     * Znajduje akcję na podstawie tablicy argumentów zapytania podając ilość zgodnych argumentów licząc od początku.
     * 
     * @param array $args
     * @return integer ilość zgodnych argumentów
     */
    public function findAction(array $args) {
        $x       = Path::combine($this->_cachePath, 'actions.php');
        $actions = file_exists($x) ? include $x : null;

        if (empty($actions) || !$this->_config->actionCache->enabled(true, true)) {
            $actions = $this->_resolveActions();
        }

        $i     = 0;
        $found = -1;
        $count = count($args);
        $match = false;

        do {
            if ($i >= $count) {
                break;
            }

            if (in_array($args[$i], $actions)) {
                $found = $i;
            }

            $match = isset($actions[$args[$i]]);
        } while ($match && $actions = $actions[$args[$i++]]);

        return $found + 1;
    }

    /**
     * Pobiera bazową ścieżkę URL
     * 
     * @return string
     */
    public function getBaseUrl() {
        if (null === $this->_baseUrl) {
            $this->_baseUrl = $this->_config->baseUrl('/', true);
        }
        return $this->_baseUrl;
    }

}
