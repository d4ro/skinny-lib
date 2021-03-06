<?php

namespace Skinny;

use Skinny\DataObject\Store;
use Skinny\Application\Components;
use Skinny\Application\Request;
use Skinny\Application\Response;
use Skinny\Application\Router;

require_once __DIR__ . '/DataObject/Store.php';

/**
 * Główna klasa przygotowująca aplikację bazującą na podstawce Skinny.
 * Odpowiada za przygotowanie konfiguracji, ustawienie loaderów, komponentów, ustalenie routingu i wykonanie akcji.
 *
 * @author Daro
 */
class Application
{

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
     * @param string $configPath ścieżka do katalogu z konfiguracją względem miejsca, w którym tworzona jest instancja
     */
    public function __construct($configPath = 'config')
    {
        $this->_appCwd = getcwd();

        // config
        if (!isset($_SERVER['APPLICATION_ENV'])) {
            die('Application environment is not set. Application cannot be run.');
        }

        // TODO: sprawdzenie, czy nazwa env nie jest pusta i czy nie zawiera nieprawidłowych znaków
        $env = $_SERVER['APPLICATION_ENV'];

        $this->_env        = $env;
        $this->_configPath = $configPath;

        if (!is_file($configPath . '/global.conf.php')) {
            die("Application global config has not been found. File '$configPath/global.conf.php' does not exist. Application cannot be run.");
        }

        $local_config = $configPath . '/' . $env . '.conf.php';
        if (!is_file($local_config)) {
            die("Application environment config has not been found. File '$local_config' does not exist. Application cannot be run.");
        }

        $config = new Store(include $configPath . '/global.conf.php');
        if (file_exists($local_config)) {
            $config->merge(include $local_config);
        }

        $this->_config = $config;

        // internal include-driven loader
        set_include_path(dirname(__DIR__) . PATH_SEPARATOR . $this->_config->paths->library('library', true) . PATH_SEPARATOR . get_include_path());

        // settings: only if enabled
        if ($config->settings->enabled(false)) {
            require_once __DIR__ . '/Settings.php';
            $this->_settings = new Settings($configPath);
        }

        // loader
        require_once __DIR__ . '/Loader.php';
        $this->_loader = new Loader($this->_config->paths);
        $this->_loader->initLoaders($this->_config->loaders->toArray());
        $this->_loader->register();

        // components
        $this->_components = new Components($this->_config);
        $this->_components->setInitializers($this->_config->components->toArray());

        // router
        $router        = $this->_config->router->class('Skinny\Application\Router', true);
        $this->_router = new $router(
            $this->_config->paths->content('content', true), $this->_config->paths->cache('cache', true),
            $this->_config->router()
        );

        // request
        $this->_request = new Request($this->_router);
        $this->_components->setInitializers([
            'request' => function () {
                return $this->_request;
            }
        ]);

        // error handler
        $this->registerErrorHandler();

//        Application\ApplicationAware::setApplication($this);
    }

    /**
     * Pobiera nazwę środowiska uruchomieniowego aplikacji.
     * @return string
     */
    public function getEnvironment()
    {
        return $this->_env;
    }

    /**
     * Pobiera konfigurację aplikacji.
     * @param string $key
     * @return mixed
     */
    public function getConfig($key = null)
    {
        $key = (string)$key;
        if (empty($key)) {
            return $this->_config;
        }

        return $this->_config->$key(null);
    }

    /**
     * Pobiera ustawienia aplikacji.
     * @param string $key
     * @return mixed
     */
    public function getSettings($key = null)
    {
        $key = (string)$key;
        if (empty($key)) {
            return $this->_settings;
        }

        return $this->_settings->$key(null);
    }

    /**
     * Pobiera komponenty aplikacji.
     * @return Components
     */
    public function getComponents()
    {
        return $this->_components;
    }

    /**
     * Pobiera komponent aplikacji o podanej nazwie.
     * @param string $name
     * @return mixed
     */
    public function getComponent($name)
    {
        return $this->_components->getComponent($name);
    }

    /**
     * Pobiera komponent aplikacji o podanej nazwie.
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->getComponent($name);
    }

    /**
     * Pobiera obiekt routera.
     * @return Router
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * Pobiera obiekt zapytania.
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Główna pętla wykonań żądań do akcji aplikacji.
     * @param string $request_url url pierwszego żądania
     * @param array $params parametry pierwszego żądania
     * @throws \Skinny\Exception
     * @throws Action\ActionException
     */
    public function run($request_url = null, array $params = array())
    {
        if (null === $request_url) {
            $request_url = urldecode($_SERVER['REQUEST_URI']);
        }

        if (null === $this->_request->current()) {
            $this->_request->next(new Request\Step($request_url, $params));
        }

        if (null === $this->_request->getResponse()) {
            $this->_request->setResponse(new Response\Http());
        }

        $counter = $maxForwardCount = $this->_config->skinny->maxNumActionsForwarded(10);
        while ($this->_request->isStepToProceed()) {
            try {
                --$counter;

                if (!$this->_request->isResolved()) {
                    $this->_request->resolve();
                }

                if ($counter === 0) {
                    throw new Action\ActionException("Too many actions dispatched in one request: $maxForwardCount in action '{$this->_request->current()->getRequestUrl()}'. Actions: " . $this->_request->toBreadCrumbs());
                }

                $action = $this->_request->current()->getAction();
                if (null === $action) {
                    $notFoundAction     = $this->_config->actions->notFound('/notFound');
                    $accessDeniedAction = $this->_config->actions->accessDenied('/accessDenied');
                    $errorAction        = $this->_config->actions->error('/error');

                    Exception::throwIf($errorAction === $this->_request->current()->getRequestUrl(),
                        new Action\ActionException('Error handler action cannot be found.'));
                    Exception::throwIf(null === $notFoundAction && ($this->_request->getResponse()
                                ->setCode(404) || true),
                        new Action\ActionException("Cannot find action corresponding to URL '{$this->_request->current()->getRequestUrl()}'."));
                    Exception::throwIf($notFoundAction === $this->_request->current()->getRequestUrl(),
                        new Action\ActionException('Cannot find the action for handling missing actions.'));

                    $this->forwardError(['@error' => 'notFound'], $notFoundAction);
                }

                Exception::throwIf(!($action instanceof Action),
                    new Action\ActionException("Action's '{$this->_request->current()->getRequestUrl()}' object is not an instance of the Skinny\\Action base class."));

                $action->onInit();
                $action->onPrepare();
                $permission = (bool)$action->onPermissionCheck();

                if (true !== $permission) {
                    $accessDeniedAction = $this->_config->actions->accessDenied('/accessDenied');
                    $errorAction        = $this->_config->actions->error('/error');
                    $notFoundAction     = $this->_config->actions->notFound('/notFound');

                    Exception::throwIf($errorAction === $this->_request->current()->getRequestUrl(),
                        new Action\ActionException('Access denied occured in error handler action.'));
                    if ($notFoundAction === $this->_request->current()->getRequestUrl()) {
                        $this->forwardError([
                            '@error'     => 'exception',
                            '@exception' => new Action\ActionException('Access denied occured in not found handler action.')
                        ],
                            $errorAction);
                    }

                    if (null !== $accessDeniedAction) {
                        $discarded = $this->_request->forceNext(new Request\Step($accessDeniedAction,
                            ['@error' => 'accessDenied']));
                        $this->_request->next()->setParams(['@discardedSteps' => $discarded]);
                        $this->_request->proceed();
                        continue;
                    } else {
                        header('HTTP/1.1 403 Forbidden', true, 403);
                        echo 'Forbidden';
                        exit();
                    }
                }

                $action->onAction();
                $action->onComplete();
            } catch (\Skinny\Action\ForwardException $e) {

            } catch (\Exception $e) {
                // get URL of error action
                $errorAction = $this->_config->actions->error('/error');

                // check for exception in params and attach it to $e if found
                $related = $this->_request->current()->getParam('@error');
                if (null !== $related) {
                    $related = ['@error' => $related];
                    if (null !== $this->_request->current()->getParam('@exception')) {
                        $related['@exception'] = $this->_request->current()->getParam('@exception');
                    }
                    if (null !== $this->_request->current()->getParam('@lastError')) {
                        $related['@lastError'] = $this->_request->current()->getParam('@lastError');
                    }
                    if (null !== $this->_request->current()->getParam('@discardedSteps')) {
                        $related['@discardedSteps'] = $this->_request->current()->getParam('@discardedSteps');
                    }
                    $e = new Exception("(with related error data) {$e->getMessage()}", 0, $e, $related);
                }

                // rethrow exceptions that error action cannot handle
                Exception::throwIf($e instanceof Action\ActionException, $e);
                Exception::throwIf(null === $errorAction, $e);
                Exception::throwIf($errorAction === $this->_request->current()->getRequestUrl(),
                    new Action\ActionException("Uncaught exception in error handler action: {$e->getMessage()}", 0, $e));

                // forward to error action
                try {
                    $this->forwardError(['@error' => 'exception', '@exception' => $e], $errorAction);
                } catch (Action\ForwardException $ex) {

                }
            }

            $this->_request->proceed();
        }

//        $this->_response->respond();
    }

    /**
     * Stwierdza, czy żądanie posiada następny krok do obsłużenia.
     * Jeżeli posiada, aktualny jest kończony.
     * @return boolean
     */
//    protected function isRequestForwarded() {
//        $forwarded = null !== $this->_request->next();
//        if ($forwarded) {
//            $this->_request->proceed();
//        }
//        return $forwarded;
//    }

    protected function forwardError($params, $errorAction = null)
    {
        // TODO: przemyśleć nazwę metody
        if (null === $errorAction || !is_string($errorAction)) {
            $errorAction = $this->_config->actions->error('/error');
        }

        Exception::throwIf(null === $errorAction, new Action\ActionException('Error handler action is not defined.'));

        $discarded = $this->_request->forceNext(new Request\Step($errorAction, $params));
        $this->_request->next()->setParams(['@discardedSteps' => $discarded]);
//        $this->_request->proceed();

        throw new Action\ForwardException();
    }

    protected function registerErrorHandler()
    {
        set_error_handler(array(
            $this, 'errorHandler'
        ));

        register_shutdown_function(array(
            $this, 'shutdownHandler'
        ));
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $errorReporting = error_reporting();
        if (!$errorReporting) {
            return true;
        }

        $lastError        = ['type' => $errno, 'message' => $errstr, 'file' => $errfile, 'line' => $errline];
        $this->_lastError = $lastError;

        return $this->handleLastError($lastError);
    }

    public function shutdownHandler()
    {
        // TODO: do uzupełnienia i przeprowadzenia pełnych testów
        $lastError = $this->getLastError();
        if (!$lastError) {
            return;
        }

        $this->handleLastError($lastError);
    }

    protected function handleLastError(array $lastError)
    {
        if (!isset($lastError['type'])) {
            return false;
        }

        switch ($lastError['type']) {
            // notice
            case E_NOTICE:
            case E_USER_NOTICE:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_STRICT:
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
        $errorAction = $this->_config->actions->error('/error');

        Exception::throwIf(null === $this->_request->current(),
            new Action\ActionException("Error occured in Application: {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}.",
                0, null, $lastError));
        Exception::throwIf(null === $errorAction,
            new Action\ActionException("Error handler action is not defined to handle an error: {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}.",
                0, null, $lastError));
        Exception::throwIf($errorAction === $this->_request->current()->getRequestUrl(),
            new Action\ActionException("Error occured in error handler action to handle an error: {$lastError['message']} in {$lastError['file']} on line {$lastError['line']}.",
                0, null, $lastError));

        try {
            $this->forwardError(['@error' => 'fatal', '@lastError' => $lastError], $errorAction);
        } catch (Action\ForwardException $ex) {

        }

        $this->_request->proceed();
        $this->run();
        exit();
        return false;
    }

    protected function getLastError()
    {
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
