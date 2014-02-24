<?php

namespace Skinny;

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
     * Konstruktor obiektu żądania.
     * @param Router\RouterInterface $router instancja routera do rozwiązywania żądania
     */
    public function __construct($router = null) {
        $this->_steps = array();
        $this->_stepCount = 0;
        $this->_current = -1;
        $this->_router = $router;
    }

    /**
     * Pobiera aktualny krok żądania.
     * @return Request\Step
     */
    public function current() {
        if ($this->_current < 0 || $this->_stepCount <= $this->_current)
            return null;
        return $this->_steps[$this->_current];
    }

    /**
     * Pobiera ostatni zainicjalizowany krok żądania (w tym jeszcze nieobsłużony).
     * @return Request\Step
     */
    public function last() {
        return $this->_steps[$this->_stepCount - 1];
    }

    /**
     * Pobiera pierwszy (oryginalny) krok żądania.
     * @return Request\Step
     */
    public function first() {
        if ($this->_stepCount < 1)
            return null;
        return $this->_steps[0];
    }

    /**
     * Pobiera poprzednio wykonany krok żądania.
     * @return Request\Step
     */
    public function previous() {
        if ($this->_current < 1)
            return null;
        return $this->_steps[$this->_current - 1];
    }

    /**
     * Dodaje kolejny krok do żądania do obsłużenia.
     * Zwraca kolejny krrok lub aktualnie dodany.
     * @param Request\Step $step
     * @return Request\Step
     */
    public function next($step = null) {
        // TODO: możliwy błąd, gdy ktoś forwaduje kilka razy pod rząd - wtedy po kolei wszystkie żądania będą wykonywane
        // TODO: z drugiej strony to może być celowe działanie
        if (null === $step) {
            if ($this->_current < $this->_stepCount - 1)
                return $this->_steps[$this->_current + 1];
            return null;
        }

        $this->_steps[$this->_current + 1] = $step;
        ++$this->_stepCount;

        $current = $this->current();
        if (null !== $current)
            $current->next($step)->previous($current);
        else
            ++$this->_current;
        return $step;
    }

    /**
     * Dodaje kolejny krok jako następny po aktualnie obsługiwanym.
     * Wszyskie kroki, ustalone jako następne przed dodaniem, zostają odrzucone i są zwracane w postaci arraya.
     * @param Request\Step $step
     * @return array
     */
    public function forceNext($step) {
        $discarded = [];
        if ($this->_stepCount > $this->_current + 1) {
            for ($i = $this->_current + 1; $i < $this->_stepCount; $i++) {
                $discarded[] = $this->_steps[$i];
                unset($this->_steps[$i]);
            }
            $this->_stepCount = $this->_current + 1;
        }
        $this->next($step);
        return $discarded;
    }

    /**
     * Kończy działanie aktualnego kroku i przechodzi do następnego.
     * Oznacza aktualny krok jako zakończony i ustawia następny jako aktualny.
     */
    public function proceed() {
        $current = $this->current();
        if (null != $current)
            $current->setProcessed(true);
        ++$this->_current;
    }

    /**
     * Stwierdza, czy wszystkie kroki żądania zostały przetworzone.
     * @return boolean
     */
    public function isProcessed() {
        $current = $this->current();
        return(null === $current || $current->isProcessed() && null === $current->next());
    }

    /**
     * Stwierdza, czy aktualny krok żądania został rozwiązany przez router.
     * @return type
     */
    public function isResolved() {
        $current = $this->current();
        //if(null === $current && ($current = $this->next()))
        return(null === $current || $current->isResolved());
    }

    /**
     * Rozwiązuje aktualny krok żądania.
     * Jeżeli aktualny krok jest już rozwiązany, przechodzi do kolejnego i go rozwiązuje.
     * @return type
     */
    public function resolve() {
        $current = $this->current();
        if (null === $current)
            return;

        if ($current->isResolved()) {
            if (null !== $current->next()) {
                ++$this->_current;
                $current = $this->current();
            }
            else
                return;
        }

        $current->resolve($this->getRouter());
    }

    /**
     * Ustawia router używany do rozwiązania kroków żądania.
     * @param \Skinny\Router\RouterInterface $router
     */
    public function setRouter(Router\RouterInterface $router) {
        $this->_router = $router;
    }

    /**
     * Pobiera obiekt routera używanego do rozwiązywania kroków zapytania.
     * @return Router\RouterInterface
     */
    public function getRouter() {
        return $this->_router;
    }

}