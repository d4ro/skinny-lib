<?php

namespace Skinny\Data\Validator;

/**
 * Klasa simple służy do obsługi customowych walidacji klasy Validate.
 * 
 * Jako argument konstruktowa przyjmuje funkcję (Closure) zwracającą wynik walidacji.
 * Klasa binduje automatycznie podanej funkcji dla $this wartość stworzonego obiektu klasy Simple, <br/>
 * która przechowuje m.in. walidowaną wartość ($this->value).
 */
class Simple extends ValidatorBase {

    /**
     * Przechowuje funkcję walidującą.
     * @var \Closure
     */
    protected $customValidator = null;
    
    /**
     * Tablica posiadająca klucz o nazwie pola oraz jego wartość
     * @var array
     */
    public $dataValue = null;

    /**
     * Przechowuje walidowaną wartość.
     * @var mixed
     */
    public $value = null;
    
    /**
     * Przechowuje wskaźnik na bieżący poziom obiektu validate
     * @var \Skinny\Data\Validate
     */
    public $item = null;
    
    /**
     * Wskaźnik na obiekt rodzica
     * @var \Skinny\Data\Validate
     */
    public $parent = null;
    
    /**
     * Wskaźnik na obiekt główny
     * @var \Skinny\Data\Validate
     */
    public $root = null;

    /**
     * Konstruktor sprawdza podany walidator i zapamiętuje go pod odpowiednią zmienną klasy.
     * 
     * @param \Closure $validator
     * @throws exception Zrzuca błąd gdy walidator nie jest poprawny.
     */
    public function __construct($validator) {
        if (!isset($validator)) {
            throw new exception("Custom validator has not been set");
        }
        if (!($validator instanceof \Closure)) {
            throw new exception("Custom validator must be a Closure");
        }

        $this->customValidator = $validator;
    }

    /**
     * Wywołanie walidacji powoduje zbindowanie pod $this dla funkcji walidującej obiektu tej klasy <br/>
     * oraz ustawienie walidowanej wartości. Następnie funkcja walidująca jest wywołana i zwracany jest jej wynik.
     * 
     * @param mixed $value
     * @return boolean
     */
    public function isValid($value) {
        $this->dataValue = [
            $this->item->getName() => $value
        ];
        $this->value = $value;

        $result = (false !== (boolean) call_user_func($this->customValidator, $this->value, $this->item)); // Wywołanie customowego walidatora
        // jeżeli zwrócono wynik funkcji (false) a nie wystąpiły żadne błędy (nie dodano ich przy użyciu funkcji error) to należy dodać błąd domyślny
        if (!$result && empty($this->_errors)) {
            $this->error();
        }

        return empty($this->_errors);
    }
    
    /**
     * Alias do metody value z obiektu $this->item
     * @param mixed $value
     * @return Simple
     */
    public function value($value = null) {
        $this->item->value($value);
        return $this;
    }

    /**
     * Funkcja ustawia customowe komunikaty o błędach.
     * 
     * @param string|array $errors
     */
    public function setErrors($errors) {
        foreach ($errors as $key => &$error) {
            if (isset($this->_messagesTemplates[$key])) {
                $error = $this->_messagesTemplates[$key];
            }
        }
        $this->_errors = $errors;
    }

}
