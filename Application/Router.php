<?php

namespace Skinny\Application;

use Skinny\Path;
use Skinny\DataObject\Store;
use Skinny\Action\ActionException;
use Skinny\Application\Router\Container;

/**
 * Klasa obiektu routera.
 * Router ma za zadanie przeparsować request url w celu określenia akcji do wykonania.
 */
class Router extends Router\RouterBase {

    private $_eventCounter = 100;

    /**
     * Konstruktor obiektu routera. Pobiera ścieżkę zawartości aplikacji i cache'u oraz ustawnia router podaną konfiguracją.
     * 
     * @param string $contentPath
     * @param string $cachePath
     * @param Store|array $config
     */
    public function __construct($contentPath, $cachePath, $config = array()) {
        $this->_contentPath = $contentPath;
        $this->_cachePath = $cachePath;
        $this->_config = ($config instanceof Store) ? $config : new Store($config);
    }

    protected function _setParam(&$params, $key, $value) {
        $key = $this->_trueKey(urldecode($key));
        $key = explode('/', $key);

        $cursor = &$params;
        $keyLength = count($key);
        for ($i = 0; $i < $keyLength; $i++) {
            $keyPart = $key[$i];
            $lastPart = !($i + 1 < $keyLength);

            // czy część klucza jest pusta - jeżeli tak, ma być numerem kolejnym
            if (empty($keyPart)) {
                $cursor[] = $lastPart ? $value : [];
                end($cursor);
                $lastInsertKey = key($cursor);
                $cursor = &$cursor[$lastInsertKey];
                continue;
            }

            // ostatnia część klucza zawsze wskazuje na wartość
            if ($lastPart) {
                $cursor[$keyPart] = $value;
                break;
            }

            // jedziemy dalej, dlatego potrzebujemy arraya
            if (!is_array($cursor[$keyPart])) {
                $cursor[$keyPart] = [];
            }

            $cursor = &$cursor[$keyPart];
            continue;
        }
    }

    private function _raiseEvent($eventName, $params) {
        if ($this->_config->events->$eventName instanceof \Closure) {
            $result = call_user_func_array($this->_config->events->$eventName, $params); //    $this->_config->events->beforeRouting($requestUrl, $container);

            if (!$result) {
                $this->_eventCounter--;
                if (!$this->_eventCounter) {
                    throw new \Skinny\Exception('Routing cannot complete at event "' . $eventName . '"');
                }
            }

            return $result;
        }

        return true;
    }

    /**
     * Pobiera trasę z adresu URL. Wyciąga z niej żądaną akcję, parametry i argumenty, zapisuje do kontenera i go zwraca.
     * 
     * @param string $requestUrl
     * @param \Skinny\Router\Container\ContainerInterface $container
     * @return \Skinny\Router\Container\ContainerInterface
     */
    public function getRoute($requestUrl, Container\ContainerInterface $container = null) {
        // jeżeli nie ma gdzie składować wyników, tworzymy nowy kontener
        if (null === $container) {
            $container = new Container();
        }

        $this->_raiseEvent('onBeforeRouting', [$requestUrl, $container]);


        onQuestionMarkParams:
        // obsługa parametrów po "?"
        $questionMarkParams = [];
        if (false !== ($questionMarkIndex = strpos($requestUrl, '?'))) {
            $apString = substr($requestUrl, $questionMarkIndex + 1);
            $requestUrl = substr($requestUrl, 0, $questionMarkIndex);
            parse_str($apString, $questionMarkParams);
        }

        if (!$this->_raiseEvent('onQuestionMarkParams', [$requestUrl, $container, &$questionMarkParams])) {
            goto onQuestionMarkParams;
        }


        onBaseUrl:
        // pobieramy ścieżkę bazową aplikacji
        $baseUrl = $this->getBaseUrl();
        if ($baseUrl && strpos($requestUrl, "$baseUrl") === 0) {
            $requestUrl = substr($requestUrl, strlen($baseUrl));
        }

        if (!$this->_raiseEvent('onBaseUrl', [$requestUrl, $container, &$baseUrl])) {
            goto onBaseUrl;
        }

        $container->setBaseUrl($baseUrl);


        onRequestArgs:
        // ustawiamy argumenty wywołania
        $requestUrl = ltrim($requestUrl, '/');
        if (empty($requestUrl)) {
            $requestUrl = 'index';
        }
        $args = explode('/', $requestUrl);

        if (!$this->_raiseEvent('onRequestArgs', [$requestUrl, $container, &$args])) {
            goto onRequestArgs;
        }

        $container->resetArgs($args);


        onFindAction:
        // określamy akcję
        $actionLength = $this->findAction($args);
        if (!$actionLength) {
            if (!$this->_raiseEvent('onActionNotFound', [$requestUrl, $container, &$args])) {
                goto onFindAction;
            }
        } else {
            if (!$this->_raiseEvent('onActionFound', [$requestUrl, $container, &$args, $actionLength])) {
                goto onFindAction;
            }

            $actionParts = array_slice($args, 0, $actionLength);
            $container->setActionParts($actionParts);
            $actionClassName = '\\content\\' . implode('\\', $actionParts);

            if (!class_exists($actionClassName, false)) {
                $actionFile = Path::combine($this->_contentPath, $actionParts) . '.php';
                include $actionFile;
            }
            \Skinny\Exception::throwIf(!class_exists($actionClassName, false), new ActionException("Action '$actionClassName' is defined but its class is not found."));
            $container->setAction(new $actionClassName());
        }

        if (!$this->_raiseEvent('onActionCreated', [$requestUrl, $container, &$args, $actionLength])) {
            goto onFindAction;
        }


        onParamsResolved:
        // określamy parametry
        $params = array();
        for ($i = $actionLength; $i < count($args); $i += 2) {
            if (empty($args[$i])) {
                continue;
            }

            if (isset($args[$i + 1])) {
                $this->_setParam($params, $args[$i], $args[$i + 1]);
//                $params[$args[$i]] = $args[$i + 1];
            } else if (count($args) == $i + 1) {
                $this->_setParam($params, $args[$i], '');
//                $params[$args[$i]] = '';
            }
        }

        if (!$this->_raiseEvent('onParamsResolved', [$requestUrl, $container, &$params])) {
            goto onParamsResolved;
        }

        $container->setParams($params);
        $container->setParams($questionMarkParams);


        return $container;
    }

    /**
     * Tworzy ścieżkę na podstawie stringa zawierającego nawiasy kwadratowe
     * odzwierciedlającego jakąś nazwę z dowolnym załebieniem.
     * W rezultacie zamiast nazwa[test][test2] otrzymamy:
     * nazwa/test/test2
     * 
     * @param string $key
     * @return string
     */
    public function getMultiDimensionalKeyPath($key) {
        if (!empty($key) && is_string($key)) {
            return $this->_trueKey($key);
        }
        return '';
    }

}
