<?php

namespace Skinny;

use Skinny\Application\Components;
use Skinny\Application\Request;
use Skinny\Application\Response;
use Skinny\Application\Router;

/**
 * Główna klasa przygotowująca aplikację bazującą na podstawce Skinny.
 * Odpowiada za przygotowanie konfiguracji, ustawienie loaderów, komponentów, ustalenie routingu i wykonanie akcji.
 *
 * @author Daro
 */
class Application {

    /**
     * Nazwa środowiska uruchomieniowego aplikacji
     * @var string
     */
    protected $_env;

    /**
     * Konfiguracja aplikacji
     * @var Store
     */
    protected $_config;

    /**
     * Dynamiczne ustawienia aplikacji
     * @var Settings
     */
    protected $_settings;

    /**
     * Komponent loadera zarządzający dynamicznym ładowaniem plików klas PHP
     * @var Loader
     */
    protected $_loader;

    /**
     * Dynamiczne komponenty aplikacji
     * @var Components
     */
    protected $_components;

    /**
     * Obiekt routera rozwiązujący ścieżki do akcji
     * @var Router
     */
    protected $_router;

    /**
     * Obiekt żądania posiadający informacje o wszystkich krokach żądań
     * @var type 
     */
    protected $_request;

    /**
     * Obiekt odpowiedzi zarządzający informacją zwrotną
     * @var Response\ResponseInterface
     */
    protected $_response;

    /**
     * Okresla katalog roboczy aplikacji, czyli ten, który był ustawiony przed jej inicjalizacją.
     * Wymagane, aby przywrócić katalog roboczy po jego samoczynnej zmianie w obsłudze shutdown.
     * @var string
     */
    protected $_appCwd;

    /**
     * Zawiera informacje o ostatnim napotkanym błędzie, warningu lub notisie.
     * @var array|null
     */
    protected $_lastError = null;

    /**
     * Konstruktor obiektu aplikacji Skinny.
     * @param string $config_path ścieżka do katalogu z konfiguracją względem miejsca, w którym tworzona jest instancja
     */
    public function __construct($config_path = 'config') {
        $this->_appCwd = getcwd();

        // config
        require_once __DIR__ . '/Store.php';

        if (!isset($_SERVER['APPLICATION_ENV'])) {
            die('Application environment is not set. Application cannot be run.');
        }

        // todo: sprawdzenie, czy nazwa env nie jest pusta i czy nie zawiera nieprawidłowych znaków

        $env = $_SERVER['APPLICATION_ENV'];
        $config = new Store(include $config_path . '/global.conf.php');
        if (file_exists($local_config = $config_path . '/' . $env . '.conf.php')) {
            $config->merge(include $local_config);
        }

        $this->_env = $env;
        $this->_config = $config;

        // internal include-driven loader
        set_include_path(dirname(__DIR__) . PATH_SEPARATOR . $this->_config->paths->library('library', true) . PATH_SEPARATOR . get_include_path());

        // settings: only if enabled
        if ($config->settings->enabled(false)) {
            require_once 'Skinny/Settings.php';
            $this->_settings = new Settings($config_path);
        }

        // loader
        require_once 'Skinny/Loader.php';
        $this->_loader = new Loader($this->_config->paths);
        $this->_loader->initLoaders($this->_config->loaders->toArray());
        $this->_loader->register();

        // bootstrap
        $this->_components = new Components($this->_config);
        $this->_components->setInitializers($this->_config->components->toArray());

        //router
        $this->_router = new Router(
                $this->_config->paths->content('content', true), $this->_config->paths->cache('cache', true), $this->_config->router()
        );

        //request
        $this->_request = new Request($this->_router);

        $this->registerErrorHandler();
    }

    /**
     * Pobiera nazwę środowiska uruchomieniowego aplikacji.
     * @return string
     */
    public function getEnvironment() {
        return $this->_env;
    }

    /**
     * Pobiera konfigurację aplikacji.
     * @param string $key
     * @return mixed
     */
    public function getConfig($key = null) {
        $key = (string) $key;
        if (empty($key))
            return $this->_config;

        return $this->_config->$key(null);
    }

    /**
     * Pobiera ustawienia aplikacji.
     * @param string $key
     * @return mixed
     */
    public function getSettings($key = null) {
        $key = (string) $key;
        if (empty($key))
            return $this->_settings;

        return $this->_settings->$key(null);
    }

    /**
     * Pobiera komponenty aplikacji.
     * @return Components
     */
    public function getComponents() {
        return $this->_components;
    }

    /**
     * Pobiera komponent aplikacji o podanej nazwie.
     * @param string $name
     * @return mixed
     */
    public function getComponent($name) {
        return $this->_components->getComponent($name);
    }

    /**
     * Pobiera komponent aplikacji o podanej nazwie.
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        return $this->getComponent($name);
    }

    /**
     * Pobiera obiekt routera.
     * @return Router
     */
    public function getRouter() {
        return $this->_router;
    }

    /**
     * Pobiera obiekt zapytania.
     * @return Request
     */
    public function getRequest() {
        return $this->_request;
    }

    /**
     * Pobiera obiekt odpowiedzi.
     * @return Response\ResponseInterface
     */
    public function getResponse() {
        return $this->_response;
    }

    /**
     * Główna pętla wykonań żądań do akcji aplikacji.
     * @param string $request_url url pierwszego żądania
     * @param array $params parametry pierwszego żądania
     * @throws \Skinny\Exception
     * @throws Action\ActionException
     */
    public function run($request_url = null, array $params = array()) {
        if (null === $request_url)
            $request_url = $_SERVER['REQUEST_URI'];

        if (null === $this->_request->current())
            $this->_request->next(new Request\Step($request_url, $params));

        if (null === $this->_response)
            $this->_response = new Response\Http();

        $counter = 0;
        while (!$this->_request->isProcessed()) {
            try {
                $counter++;
                if ($counter >= 10) {
                    var_dump($this->_request);
                    die();
                }
                Exception::throwIf($counter >= 10, new Action\ActionException("Too many forwards: 10 in action '{$this->_request->current()->getRequestUrl()}'."));

                if (!$this->_request->isResolved()) {
                    $this->_request->resolve();
                }

                $action = $this->_request->current()->getAction();
                if (null === $action) {
                    $notFoundAction = $this->_config->actions->notFound(null);
                    Exception::throwIf(null === $notFoundAction && ($this->_response->setCode(404) || true), new Action\ActionException("Cannot find action corresponding to URL '{$this->_request->current()->getRequestUrl()}'."));
                    Exception::throwIf($notFoundAction === $this->_request->current()->getRequestUrl(), new Action\ActionException('Cannot find the action for handling missing actions.'));

                    $this->_request->next(new Request\Step($notFoundAction, ['error' => 'notFound']));
                    $this->_request->proceed();
                    continue;
                }

                Exception::throwIf(!($action instanceof Action), new Action\ActionException('Action object is not an instance of the Skinny\Action base class.'));

                $action->setApplication($this);
                $action->_init();

                try {
                    $permission = $action->_permit();
                } catch (\Skinny\Action\ForwardException $e) {
                    
                }

                if ($this->isRequestForwarded())
                    continue;

                if (true !== $permission) {
                    $errorAction = $this->_config->actions->accessDenied(null);
                    if (null !== $errorAction) {
                        $discarded = $this->_request->forceNext(new Request\Step($errorAction, ['error' => 'accessDenied']));
                        $this->_request->next()->setParams(['discardedSteps' => $discarded]);
                        $this->_request->proceed();
                        continue;
                    } else {
                        header('HTTP/1.0 403 Forbidden');
                        echo 'Forbidden';
                        exit();
                    }
                }

                try {
                    $action->_prepare();
                } catch (\Skinny\Action\ForwardException $e) {
                    
                }

                if ($this->isRequestForwarded())
                    continue;

                try {
                    $action->_action();
                } catch (\Skinny\Action\ForwardException $e) {
                    
                }

                if ($this->isRequestForwarded())
                    continue;

                try {
                    $action->_cleanup();
                } catch (\Skinny\Action\ForwardException $e) {
                    
                }

                $this->_request->proceed();
            } catch (\Exception $e) {
                if ($e instanceof Action\ActionException)
                    throw $e;

                $errorAction = $this->_config->actions->error(null);

                Exception::throwIf(null === $errorAction, $e);
                Exception::throwIf($errorAction === $this->_request->current()->getRequestUrl(), new Action\ActionException("Uncaught exception in error action: {$e->getMessage()}", 0, $e));

                $discarded = $this->_request->forceNext(new Request\Step($errorAction, ['error' => 'exception', 'exception' => $e]));
                $this->_request->next()->setParams(['discardedSteps' => $discarded]);
                $this->_request->proceed();
                continue;
            }
        }

//        $this->_response->respond();
    }

    /**
     * Stwierdza, czy żądanie posiada następny krok do obsłużenia.
     * Jeżeli posiada, aktualny jest kończony.
     * @return boolean
     */
    protected function isRequestForwarded() {
        $forwarded = null !== $this->_request->next();
        if ($forwarded)
            $this->_request->proceed();
        return $forwarded;
    }

    protected function registerErrorHandler() {
        set_error_handler(array(
            $this, 'errorHandler'
        ));

        register_shutdown_function(array(
            $this, 'shutdownHandler'
        ));
    }

    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $errorReporting = error_reporting();
        if (!$errorReporting)
            return true;

        switch ($errno) {
            // notice
            case E_NOTICE:
            case E_DEPRECATED:
            case E_STRICT:
            case E_USER_DEPRECATED:
                // ignorowanie
                return false;
                break;

            // warning
            case E_COMPILE_WARNING:
            case E_CORE_WARNING:
            case E_USER_WARNING:
            case E_WARNING:
                // logowanie, ale idziemy dalej
                // TODO: logowanie warninga
                return false;
                break;

            // error
            case E_COMPILE_ERROR:
            case E_CORE_ERROR:
            case E_ERROR:
            case E_PARSE:
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
                // obsługa błędu
                break;

            default:
                break;
        }

        // obsługa błędu
        $errorAction = $this->_config->actions->error(null);
        if (null !== $errorAction) {
            $discarded = $this->_request->forceNext(new Request\Step($errorAction, ['error' => 'fatal', 'step' => $this->_request->current(), 'lastError' => ['type' => $errno, 'message' => $errstr, 'file' => $errfile, 'line' => $errline]]));
            $this->_request->next()->setParams(['discardedSteps' => $discarded]);
            $this->_request->proceed();
        }

        $this->run();
        exit();
        return true;
    }

    public function shutdownHandler() {
        $lastError = $this->getLastError(); // error_get_last();
        if (!$lastError)
            return;

        switch ($lastError['type']) {
            // notice
            case E_NOTICE:
            case E_DEPRECATED:
            case E_STRICT:
            case E_USER_DEPRECATED:
                // ignorowanie
                return;
                break;

            // warning
            case E_COMPILE_WARNING:
            case E_CORE_WARNING:
            case E_USER_WARNING:
            case E_WARNING:
                // logowanie, ale idziemy dalej
                // TODO: logowanie warninga
                return;
                break;

            // error
            case E_ERROR:
//                if (strpos($lastError['message'], 'Uncaught exception') === 0)
//                    return;
            case E_COMPILE_ERROR:
            case E_CORE_ERROR:
            case E_PARSE:
            case E_RECOVERABLE_ERROR:
            case E_USER_ERROR:
                // obsługa błędu
                break;

            default:
                break;
        }

        chdir($this->_appCwd);
        ob_clean();

        // obsługa błędu
        $errorAction = $this->_config->actions->error(null);
        if (null !== $errorAction) {
            $discarded = $this->_request->forceNext(new Request\Step($errorAction, ['error' => 'fatal', 'step' => $this->_request->current(), 'lastError' => $lastError]));
            $this->_request->next()->setParams(['discardedSteps' => $discarded]);
            $this->_request->proceed();
        }

        $this->run();
        exit();
    }

    protected function getLastError() {
        $lastError = error_get_last();
        if (null === $lastError) {
            $this->_lastError = null;
            return null;
        }

        if (null === $this->_lastError || array_diff_assoc($this->_lastError, $lastError)) {
            $this->_lastError = $lastError;
            return $lastError;
        }

        return null;
    }

}
