<?php

namespace Skinny\Application;

use Skinny\IOException;
use Skinny\Path;
use Skinny\Store;
use Skinny\Application\Router\Container;

/**
 * Klasa obiektu routera.
 * Router ma za zadanie przeparsować request url i porównać ze ścieżką akcji w celu określenia akcji do wykonania.
 *
 * @author Daro
 */
class Router implements Router\RouterInterface {

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
     * @var Store
     */
    protected $_config;

    /**
     * Konstruktor obiektu routera. Pobiera ścieżkę zawartości aplikacji i cache'u oraz ustawnia router podaną konfiguracją.
     * @param string $contentPath
     * @param string $cachePath
     * @param Store|array $config
     */
    public function __construct($contentPath, $cachePath, $config = array()) {
        $this->_contentPath = $contentPath;
        $this->_cachePath = $cachePath;
        $this->_config = ($config instanceof Store) ? $config : new Store($config);
    }

    /**
     * Pobiera trasę z adresu URL. Wyciąga z niej żądaną akcję, parametry i argumenty, zapisuje do kontenera i go zwraca.
     * @param string $requestUrl
     * @param \Skinny\Router\Container\ContainerInterface $container
     * @return \Skinny\Router\Container\ContainerInterface
     */
    public function getRoute($requestUrl, Container\ContainerInterface $container = null) {
        // jeżeli nie ma gdzie składować wyników, tworzymy nowy kontener
        if (null === $container)
            $container = new Container();

        // obsługa parametrów po "?"
        $additionalParams = [];
        if (false !== ($qmIndex = strpos($requestUrl, '?'))) {
            $apString = substr($requestUrl, $qmIndex + 1);
            $requestUrl = substr($requestUrl, 0, $qmIndex);
            parse_str($apString, $additionalParams);
        }

        // pobieramy ścieżkę bazową aplikacji
        $baseUrl = $this->getBaseUrl();
        if ($baseUrl && strpos($requestUrl, "$baseUrl") === 0) {
            $requestUrl = substr($requestUrl, strlen($baseUrl));
        }

        // ustawiamy argumenty wywołania
        $requestUrl = ltrim($requestUrl, '/');
        if (empty($requestUrl))
            $requestUrl = 'index';
        $args = explode('/', $requestUrl);

        $container->resetArgs($args);

        // określamy akcję
        $actionLength = $this->findAction($args);
        if ($actionLength > 0) {
            $actionParts = array_slice($args, 0, $actionLength);
            $container->setActionParts($actionParts);
            $actionClassName = '\\content\\' . implode('\\', $actionParts);
            try {
                if (!class_exists($actionClassName, false)) {
                    $actionFile = Path::combine($this->_contentPath, $actionParts) . '.php';
                    require $actionFile;
                }
                $container->setAction(new $actionClassName());
            } catch (Exception $e) {
                
            }
        }

        // określamy parametry
        $params = array();
        for ($i = $actionLength; $i < count($args); $i += 2) {
            if (!empty($args[$i])) {
                if (isset($args[$i + 1]))
                    $params[$args[$i]] = $args[$i + 1];
                else if (count($args) == $i + 1)
                    $params[$args[$i]] = '';
            }
        }

        $container->setParams($params);
        $container->setParams($additionalParams);

        return $container;
    }

    /**
     * Pobiera bazową ścieżkę URL
     * @return string
     */
    public function getBaseUrl() {
        if (null === $this->_baseUrl)
            $this->_baseUrl = $this->_config->baseUrl('/', true);
        return $this->_baseUrl;
    }

    /**
     * Znajduje akcję na podstawie tablicy argumentów zapytania podając ilość zgodnych argumentów licząc od początku.
     * @param array $args
     * @return integer ilość zgodnych argumentów
     */
    public function findAction(array $args) {
        $x = Path::combine($this->_cachePath, 'actions.php');
        $actions = file_exists($x) ? include $x : null;

//        if (empty($actions))
        $actions = $this->resolveActions();

        $i = 0;
        $found = -1;
        $count = count($args);
        $match = false;

        do {
            if ($i >= $count)
                break;

            if (in_array($args[$i], $actions))
                $found = $i;

            $match = isset($actions[$args[$i]]);
        } while ($match && $actions = $actions[$args[$i++]]);

        return $found + 1;
    }

    /**
     * Iteruje po katalogu zawartości aplikacji wyszukując pliki .php akcji.
     * Rezultat wyszukiwania zapisuje w cache.
     * @return array tablica będąca reprezentacją struktury akcji w folderze zawartości aplikacji
     * @throws IOException
     */
    protected function resolveActions() {
        if (!is_dir($this->_contentPath))
            throw new IOException('Could not read application content directory.');

        $actions = self::readDir($this->_contentPath);
        $actionsPath = Path::combine($this->_cachePath, 'actions.php');

        $file = new File($actionsPath);
        $file->write('<?php return ' . var_export($actions, true) . ';');
        return $actions;
    }

    /**
     * Odczytuje zawartość katalogu i zwraca tablicę jego zawartości, gdzie:
     * - pliki (wyłącznie z rozszerzeniem .php, niezaczynające się od kropki "."):
     *   reprezentowane są bez rozszerzenia, pod kolejnym indeksem numerycznym
     * - katalogi (niezaczynające się od kropki "."):
     *   reprezentowane są w kluczu, gdzie wartość jest rekurencyjną zawartością katalogu
     * @param string $dirPath ścieżka do katalogu
     * @return array
     */
    protected static function readDir($dirPath) {
        $dir = dir($dirPath);
        $actions = [];
        while ($item = $dir->read()) {
            $path = $dirPath . DIRECTORY_SEPARATOR . $item;
            if ($item[0] == '.')
                continue;

            if (is_dir($path)) {
                $actions[$item] = self::readDir($path);
                continue;
            }

            if (substr($item, -4) == '.php')
                $actions[] = substr($item, 0, -4);
        }
        return $actions;
    }

}
