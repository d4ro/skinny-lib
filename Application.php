<?php

namespace Skinny;

use Skinny\Application\Components;

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
     * Konstruktor obiektu aplikacji Skinny.
     * @param string $config_path ścieżka do katalogu z konfiguracją względem miejsca, w którym tworzona jest instancja
     */
    public function __construct($config_path = 'config') {
        // config
        require_once __DIR__ . '/Store.php';
        $env = isset($_SERVER['APPLICATION_ENV']) ? $_SERVER['APPLICATION_ENV'] : 'production';
        $config = new Store(include $config_path . '/global.conf.php');
        if (file_exists($local_config = $config_path . '/' . $env . '.conf.php'))
            $config->merge(include $local_config);

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

        \model\base::setApplication($this); // ustawia wskaźnik do aplikacji dla modeli poprzez \model\base
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
     * @throws Action\Exception
     */
    public function run($request_url = null, array $params = array()) {
        if (null === $request_url)
            $request_url = $_SERVER['REQUEST_URI'];

        $this->_request->next(new Request\Step($request_url, $params));
        if (null === $this->_response)
            $this->_response = new Response\Http();

        $counter = 0;
        while (!$this->_request->isProcessed()) {
            try {
                $counter++;
                if ($counter >= 10)
                    throw new Action\Exception('Too many forwards: 10 in action ' . $this->_request->current()->getRequestUrl());

                if (!$this->_request->isResolved())
                    $this->_request->resolve();

                $action = $this->_request->current()->getAction();
                if (null === $action) {
                    $notFoundAction = $this->_config->actions->notFound(null);
                    if (null !== $notFoundAction) {
                        $this->_request->next(new Request\Step($notFoundAction, ['error' => 'notFound', 'requestStep' => $this->_request->current()]));
                        $this->_request->proceed();
                        continue;
                    }
                    else
                    // TODO: błąd 404
                        throw new Action\Exception('Cannot find action corresponding to URL "' . $this->_request->current()->getRequestUrl() . '".');
                    // TODO: $this->_response->notFound();
                }

                if (!($action instanceof Action))
                    throw new Action\Exception('Action found is not an instance of the Skinny\Action base class.');

                $action->setApplication($this);
                $action->_init();

                try {
                    $permission = $action->_permit();
                } catch (\Skinny\Action\ForwardException $e) { }

                if ($this->isRequestForwarded())
                    continue;

                if (true !== $permission) {
                    $errorAction = $this->_config->actions->error(null);
                    if (null !== $errorAction) {
                        $discarded = $this->_request->forceNext(new Request\Step($errorAction, ['error' => 'accessDenied', 'requestStep' => $this->_request->current()]));
                        $this->_request->next()->setParams(['discardedSteps' => $discarded]);
                        $this->_request->proceed();
                        continue;
                    }
                    else
                    // TODO: 403
                        throw new Action\Exception('Access denied');
                }

                try {
                    $action->_prepare();
                } catch (\Skinny\Action\ForwardException $e) { }

                if ($this->isRequestForwarded())
                    continue;

                try {
                    $action->_action();
                } catch (\Skinny\Action\ForwardException $e) { }

                if ($this->isRequestForwarded())
                    continue;

                try {
                    $action->_cleanup();
                } catch (\Skinny\Action\ForwardException $e) { }

                $this->_request->proceed();
            } catch (\Exception $e) {
                $errorAction = $this->_config->actions->error(null);
                if (null === $errorAction) {
                    $discarded = $this->_request->forceNext(new Request\Step($errorAction, ['error' => 'exception', 'requestStep' => $this->_request->current(), 'exception' => $e]));
                    $this->_request->next()->setParams(['discardedSteps' => $discarded]);
                    $this->_request->proceed();
                    continue;
                }
                else
                    throw $e;
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

}