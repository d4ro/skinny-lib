<?php

namespace Skinny\Application;

/**
 * Klasa reprezentująca żądanie do aplikacji.
 * Przechowuje wszystkie kroki żądania od początku wywołań.
 *
 * @author Daro
 */
class Request {

    /**
     * Tablica kroków żądania
     * @var array
     */
    protected $_steps;

    /**
     * Ilość kroków żądania
     * @var integer
     */
    protected $_stepCount;

    /**
     * Indeks aktualnego kroku żądania
     * @var integer
     */
    protected $_current;

    /**
     * Obiekt routera
     * @var Router
     */
    protected $_router;

    /**
     * Obiekt odpowiedzi zarządzający informacją zwrotną
     * @var Response\ResponseInterface
     */
    protected $_response;

    /**
     * Konstruktor obiektu żądania.
     * 
     * @param Router\RouterInterface $router instancja routera do rozwiązywania żądania
     */
    public function __construct($router = null) {
        $this->_steps = array();
        $this->_stepCount = 0;
        $this->_current = -1;
        $this->_router = $router;
//        $this->log('Konstruktor');
    }

//    private function log($tring) {
//        $file = new \Skinny\File('kupa.txt');
//        $file->append(PHP_EOL .
//                '----- ' . date('y-m-d  H:i:s') . ' -----' . PHP_EOL . $tring . PHP_EOL . count($this->_steps) . " stepów, teraz {$this->_current}" . PHP_EOL);
//    }

    /**
     * Pobiera aktualny krok żądania.
     * 
     * @return Request\Step
     */
    public function current() {
//        $this->log('current()');
        if ($this->_current < 0 || $this->_stepCount <= $this->_current) {
            return null;
        }
        return $this->_steps[$this->_current];
    }

    /**
     * Pobiera ostatni zainicjalizowany krok żądania (w tym jeszcze nieobsłużony).
     * 
     * @return Request\Step
     */
    public function last() {
//        $this->log('last()');
        return $this->_steps[$this->_stepCount - 1];
    }

    /**
     * Pobiera pierwszy (oryginalny) krok żądania.
     * 
     * @return Request\Step
     */
    public function first() {
//        $this->log('first()');
        if ($this->_stepCount < 1) {
            return null;
        }

        return $this->_steps[0];
    }

    /**
     * Pobiera poprzednio wykonany krok żądania.
     * 
     * @return Request\Step
     */
    public function previous() {
//        $this->log('previous()');
        if ($this->_current < 1) {
            return null;
        }

        return $this->_steps[$this->_current - 1];
    }

    /**
     * Dodaje kolejny krok do żądania do obsłużenia.
     * Zwraca kolejny krrok lub aktualnie dodany.
     * 
     * @param Request\Step $step
     * @return Request\Step
     */
    public function next($step = null) {
//        $this->log('next() ' . ($step ? 'z' : 'bez'));
        // TODO: możliwy błąd, gdy ktoś forwaduje kilka razy pod rząd - wtedy po kolei wszystkie żądania będą wykonywane
        // TODO: z drugiej strony to może być celowe działanie
        if (null === $step || $this->_current < $this->_stepCount - 1) {
            if ($this->_current < $this->_stepCount - 1) {
//                $this->log('po next() ' . ($step ? 'z' : 'bez'));
                return $this->_steps[$this->_current + 1];
            }

//            $this->log('po next() ' . ($step ? 'z' : 'bez'));
            return null;
        }

        $this->_steps[$this->_current + 1] = $step;
        ++$this->_stepCount;

        $current = $this->current();
        if (null !== $current) {
            $current->next($step)->previous($current);
            if ($current->isProcessed()) {
                ++$this->_current;
            }
        } else {
            ++$this->_current;
        }
//        $this->log('po next() ' . ($step ? 'z' : 'bez'));
        return $step;
    }

    /**
     * Dodaje kolejny krok jako następny po aktualnie obsługiwanym.
     * Wszyskie kroki, ustalone jako następne przed dodaniem, zostają odrzucone i są zwracane w postaci arraya.
     * 
     * @param Request\Step $step
     * @return array
     */
    public function forceNext($step) {
//        $this->log('forceNext() ' . ($step ? 'z' : 'bez'));
        $discarded = [];
        if ($this->_stepCount > $this->_current + 1) {
            for ($i = $this->_current + 1; $i < $this->_stepCount; $i++) {
                $discarded[] = $this->_steps[$i];
                unset($this->_steps[$i]);
            }
            $this->_stepCount = $this->_current + 1;
        }
        $this->next($step);
//        $this->log('po forceNext() ' . ($step ? 'z' : 'bez'));
        return $discarded;
    }

    /**
     * Kończy działanie aktualnego kroku i przechodzi do następnego.
     * Oznacza aktualny krok jako zakończony i ustawia następny jako aktualny.
     */
    public function proceed() {
//        $this->log('proceed()');
        $current = $this->current();
        if (null != $current) {
            $current->setProcessed(true);
        }

        ++$this->_current;
//        $this->log('po proceed()');
    }

    /**
     * Stwierdza, czy wszystkie kroki żądania zostały przetworzone.
     * 
     * @return boolean
     */
    public function isStepToProceed() {
//        $this->log('isStepToProceed()');
        $current = $this->current();
        $result = (null !== $current && !$current->isProcessed() || (null !== $current && null !== $current->next() && $this->proceed()));
//        $this->log('result isStepToProceed() == ' . ($result ? 'true' : 'false'));
        return $result;
    }

    /**
     * Stwierdza, czy aktualny krok żądania został rozwiązany przez router.
     * 
     * @return boolean
     */
    public function isResolved() {
//        $this->log('isResolved()');
        $current = $this->current();
        //if(null === $current && ($current = $this->next()))
        return(null === $current || $current->isResolved());
    }

    /**
     * Rozwiązuje aktualny krok żądania.
     * Jeżeli aktualny krok jest już rozwiązany, przechodzi do kolejnego i go rozwiązuje.
     */
    public function resolve() {
//        $this->log('resolve()');
        $current = $this->current();
        if (null === $current) {
            return;
        }

        if ($current->isResolved()) {
            if (null !== $current->next()) {
                ++$this->_current;
                $current = $this->current();
            } else {
                return;
            }
        }

        $current->resolve($this->getRouter());
    }

    /**
     * Ustawia router używany do rozwiązania kroków żądania.
     * 
     * @param \Skinny\Router\RouterInterface $router
     */
    public function setRouter(Router\RouterInterface $router) {
        $this->_router = $router;
    }

    /**
     * Pobiera obiekt routera używanego do rozwiązywania kroków zapytania.
     * 
     * @return Router\RouterInterface
     */
    public function getRouter() {
        return $this->_router;
    }

    /**
     * Pobiera obiekt odpowiedzi.
     * 
     * @return Response\ResponseInterface
     */
    public function getResponse() {
        return $this->_response;
    }

    /**
     * Ustawia obiekt odpowiedzi.
     * 
     * @param \Skinny\Application\Response\ResponseInterface $response
     */
    public function setResponse(Response\ResponseInterface $response) {
        $this->_response = $response;
    }

    /**
     * Zwraca informację o tym czy żądanie akceptuje application/json (w zmiennej SERVER['HTTP_ACCEPT']).
     * 
     * @return boolean
     * @todo Przemyśleć i ustandaryzować
     */
    public function acceptJson() {
        return false !== strstr($_SERVER['HTTP_ACCEPT'], 'application/json');
    }

    /**
     * Stwierdza, czy żądanie do serwera zostało wykonane metodą POST.
     * 
     * @return boolean
     */
    public function hasPost() {
        return ($_SERVER['REQUEST_METHOD'] === 'POST');
    }

    public function toBreadCrumbs() {
        $s = '';
        $i = $this->first();
        while ($i) {
            $s .= $i->getActionUrl() . ' (' . json_encode($i->getParams()) . ') > ';
            $i = $i->next();
        }
        return $s;
    }

    public function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

}
