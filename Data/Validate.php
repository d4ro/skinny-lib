<?php

namespace Skinny\Data;

/**
 * Klasa validate jest klasą umożliwiającą walidację danych wejściowych 
 * za pomocą istniejących walidatorów lub stworzonych przez siebie.
 * 
 * @todo aliasy nazw pól np. w danych do walidacji jest klucz "title", 
 * który w komunikacie ma zostać zamieniony na "tytuł"
 */
class Validate implements \IteratorAggregate {

    /**
     * Oczekuje na walidację
     */
    const STATUS_NOT_VALIDATED = 'notValidated';

    /**
     * W trakcie walidacji
     */
    const STATUS_VALIDATION_IN_PROGRESS = 'validationInProgress';

    /**
     * Walidacja zakończona
     */
    const STATUS_VALIDATED = 'validated';

    /**
     * Tablica kluczy i wartości które mają być podmienione przy komunikatach o błędach
     */
    const OPTION_MESSAGES_PARAMS = 'messagesParams';

    /**
     * Przerywa walidację gdy jedno z pól aktualnie walidowanych nie przejdzie walidacji
     */
    const OPTION_BREAK_ON_ITEM_FAILURE = 'breakOnItemFailure';

    /**
     * Przerywa walidację gdy jeden z walidatorów dla pola nie przejdzie walidacji
     */
    const OPTION_BREAK_ON_VALIDATOR_FAILURE = 'breakOnValidatorFailure';

    /**
     * Limit "głębokich" walidacji, których każde wywołanie powoduje utworzenie nowych podelementów dla dowolnego poziomu
     */
    const DEEP_VALIDATION_LIMIT = 100;

    /**
     * Przechowuje elementy walidatora. Każdy element może posiadać swoje podelementy.
     * @var array 
     */
    protected $items;

    /**
     * Wskaźnik na rodzica
     * @var validate
     */
    protected $parent = null;

    /**
     * Przechowuje wskaźnik na korzeń walidacji
     * 
     * @var validate 
     */
    private $__root = null;

    /**
     * Przechowuje kolejne klucze (name) od korzenia do danego poziomu.
     * Używane przy pobieraniu wartości bieżącego pola (po to aby dobierać się
     * do __allData za pomocą pętli).
     * 
     * @var array
     */
    private $__keysFromRoot = [];

    /**
     * Tablica przechowująca wszystkie walidatory przypisane do bieżącego pola.
     * @var array
     */
    protected $validators = [];

    /**
     * Tutaj są przechowywane walidatory dla wszystkich podwartości walidowanego zakresu danych.
     * Wszystkie walidatory each muszą być najpierw ustawione przed wywołaniem 
     * docelowej metody isValid - żeby ustawić te walidatory musimy wiedzieć z jakimi danymi
     * mamy do czynienia.
     * 
     * @var array
     */
    protected $eachValidators = [];

    /**
     * Przechowuje komunikaty o zaistniałych błędach dla bieżącego pola. Komunikaty są generowane w momencie wywołania metody validate::getErrors().
     * @var array
     */
    protected $errors = [];

    /**
     * Przechowuje nazwę/klucz bieżącego pola.
     * @var string
     */
    protected $_name = null;

    /**
     * Przechowuje ustawienia dla bieżącego pola.
     * @var array
     */
    protected $options = [
        self::OPTION_BREAK_ON_ITEM_FAILURE => false,
        self::OPTION_BREAK_ON_VALIDATOR_FAILURE => true,
        self::OPTION_MESSAGES_PARAMS => []
    ];

    /**
     * Bieżący status walidacji
     * @var string
     */
    protected $status = self::STATUS_NOT_VALIDATED;

    /**
     * Przechowuje wszystkie dane ustawione przed walidacją oraz zmerdżowane dane
     * zaraz po uruchomieniu walidacji z nowymi danymi
     * @var array
     */
    private $__allData = [];

    /**
     * Przechowuje dane walidacji tylko dla bieżącego poziomu
     * @var array
     */
    public $data = null;

    /**
     * Przechowuje wynik walidacji
     * @var boolean
     */
    protected $result = null;

    /**
     * Konstruktor
     */
    public function __construct() {
        
    }

    /**
     * Umożliwia iterowanie bezpośrednio po elementach tablicy items
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->items);
    }

    /**
     * Zwraca nazwę/klucz bieżącego pola
     * @return int|string
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Sprawdza czy istnieje rodzic dla tego poziomu
     * @return boolean
     */
    public function hasParent() {
        return $this->parent !== null;
    }

    /**
     * Zwraca obiekt rodzica danego poziomu
     * 
     * @param int $levelsUp Ile poziomów w górę chcemy się wybrać ;)
     * @return validate
     */
    public function getParent($levelsUp = 1) {
        if ($levelsUp < 1) {
            throw new ValidateValidate\Exception('Incorrect $levelsUp param');
        }

        $parent = $this->parent;
        for ($i = 1; $i < $levelsUp; $i++) {
            if ($parent && $parent->hasParent()) {
                $parent = $parent->getParent();
            } else {
                $parent = null;
                break;
            }
        }

        return $parent;
    }

    /**
     * Zwraca główny korzeń obiektu Validate
     * @return Validate
     */
    public function getRoot() {
        if ($this->__root === null) {
            if ($this->hasParent()) {
                $this->__root = $this->getParent()->getRoot();
            } else {
                $this->__root = $this;
            }
        }
        return $this->__root;
    }

    /**
     * Odczyt nieistniejącej właściwości - tworzy nowy obiekt tej klasy oraz kopiuje do niego opcje z bieżącego poziomu.
     * 
     * @param string $name Nazwa pola do walidacji
     * @return validate
     */
    public function &__get($name) {
        if (!isset($this->items[$name])) {
            $this->items[$name] = new static();
            $this->items[$name]->mergeOptions($this->options);
            $this->items[$name]->_name = $name;
            $this->items[$name]->parent = $this;

            // Budowanie kluczy od roota tak aby był do nich szybki dostęp
            $this->items[$name]->__keysFromRoot = array_merge([], $this->__keysFromRoot);
            $this->items[$name]->__keysFromRoot[] = $name;
        }
        return $this->items[$name];
    }

    /**
     * Zapis do nieistniejącej właściwości
     * 
     * @param string    $name   Nazwa pola
     * @param self      $value  Przypisywana wartość
     * @throws Validate\Exception
     */
    public function __set($name, $value) {
        if (!($value instanceof static)) {
            throw new ValidateValidate\Exception("Invalid value");
        }
        $this->items[$name] = $value;
        $this->items[$name]->_name = $name;
        $this->items[$name]->parent = $this;
    }

    /**
     * Isset lub empty na nieistniejącej właściwości.
     * 
     * @param   string $name Nazwa pola
     * @return  boolean
     */
    public function __isset($name) {
        return isset($this->items[$name]) && !($this->items[$name] instanceof self);
    }

    /**
     * Unsetowanie nieistniejącej właściwości.
     * 
     * @param string $name Nazwa pola
     */
    public function __unset($name) {
        unset($this->items[$name]);
    }

    /**
     * Uruchamia walidację dla tego pola <br/>
     * Aby walidacja przeszła poprawnie wszystkie walidatory muszą zwrócić wynik pozytywny
     * 
     * @param   mixed     $value                      Wartość do walidacji
     * @return  boolean
     */
    protected function _validate($value) {
        // Jeżeli walidacja na tym poziomie jest w trakcie wykonywania - jest to błąd...
        if ($this->status === self::STATUS_VALIDATION_IN_PROGRESS) {
//            throw new ValidateValidate\Exception("Validation is in progress");
        } else if ($this->status === self::STATUS_VALIDATED) {
            /**
             * Jeżeli ten walidator został juz zwalidowany, należy zwrócić wynik walidacji.
             * Taka sutuacja wystąpi w momencie gdy wywołano najpierw metodę isValid podając 
             * tablicę do walidacji a następnie wywołano tą metodę bez podania argumentów. 
             * Przy podaniu argumentu do funkcji isValid wynik oraz status walidacji jest resetowany.
             */
            return $this->result;
        }

        // Ustawienie statusu walidacji
        $this->setStatus(self::STATUS_VALIDATION_IN_PROGRESS);

        // Ustawienie wartości do walidacji
        $toCheck = $this->__setupToCheckValue($value);

        // Ustawienie bieżącego poziomu danych do walidacji
        $this->data = $toCheck;

        $this->result = true;
        if (!empty($this->validators) || !empty($this->eachValidators)) {
            $this->__prepareLevelValidation($toCheck); // przygotowanie walidacji każdego poziomu - m.in. "each"
            $this->result = $this->_validateItem($this, $toCheck);
        } else if (empty($this->items)) {
//            throw new ValidateValidate\Exception("No items to validate");
            // brak walidatorów oznacza poprawną walidację
        }

        /**
         * Jeżeli istnieją podelementy tego pola i ustawiono sprawdzenie rekursywne 
         * oraz wynik walidacji dla bieżącego pola jest pozytywny (lub ustawiono flagę, aby nie przerywać walidacji)
         * należy zwalidować wszystkie podelementy
         */
        if (!empty($this->items) && $this->result === true) {
            foreach ($this->items as $item) {
                if (!$item->_validate($toCheck)) {
                    $this->result = false;
                    if ($this->options[self::OPTION_BREAK_ON_ITEM_FAILURE] === true) {
                        break;
                    }
                }
            }
        }

        $this->setStatus(self::STATUS_VALIDATED);
        return $this->result;
    }

    /**
     * Funkcja zliczająca sumę elementów i podelementów dla danego poziomu
     * 
     * @return int
     */
    public function countValidators() {
        $count = count($this->validators) + count($this->eachValidators);
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $count += $item->countValidators();
            }
        }

        return $count;
    }

    /**
     * Zwraca sumę elementów dla danego poziomu razem z wszystkimi podelementami
     * 
     * @return int
     */
    public function count() {
        $count = count($this->items);
        if ($count > 0) {
            foreach ($this->items as $item) {
                $count += $item->count();
            }
        }

        return $count;
    }

    /**
     * Ustawia odpowiednią wartość walidowanego pola.
     * Metoda używana jedynie przy walidacji bieżącego poziomu.
     * @param Array|\Traversable $value
     * @return mixed
     */
    private function __setupToCheckValue($value) {
        if ($value instanceof KeyNotExist) {
            return $value;
        }

        if ($this->_name !== null) {
            if ($value instanceof \Traversable) {
                $arrayVal = (array) $value;
                if (key_exists($this->_name, $arrayVal)) {
                    $toCheck = $value->{$this->_name};
                } else {
                    $toCheck = new KeyNotExist();
                }
            } else if (is_array($value)) {
                if (key_exists($this->_name, $value)) {
                    $toCheck = $value[$this->_name];
                } else {
                    $toCheck = new KeyNotExist();
                }
            } else {
                $toCheck = $value;
            }
        } else {
            $toCheck = $value;
        }

        return $toCheck;
    }

    /**
     * Przygotowuje walidację dla bieżącego poziomu.
     * Np. Walidatory "each" lub inne niezbędne ustawienia
     * 
     * @param validate $this
     * @param array $data
     */
    private function __prepareLevelValidation($data) {
        if (!empty($this->eachValidators) && !empty($data) && !($data instanceof KeyNotExist) && (is_array($data) || $data instanceof \Traversable)) {
            // jeżeli ustawiono walidatory dla wszystkich podelementów to należy je najpierw przygotować
            foreach ($data as $k => $v) {
                foreach ($this->eachValidators as $vData) {
                    $this->$k->__prepend($vData['validator'], $vData['errorMsg'], $vData['options']);
                    $this->$k->mergeOptions($vData['options']); // TODO czy to na pewno tak ma być = opcje nadpisywane na poziomie każdego walidatora z osobna...
                }
            }

            $this->eachValidators = [];
        }
    }

    /**
     * Walidacja pojedynczego pola (do którego może być przypisanych wiele walidatorów).
     * 
     * @param   validate    $item
     * @param   mixed       $value
     * @return  boolean
     * @throws  Validate\Exception
     */
    protected function _validateItem($item, $value) {
        $item->result = true;
        foreach ($item->validators as $validator) {
            // Ustawienie customowych komunikatów wraz z przekazaniem name oraz value
            $params = array_merge(
                    ['name' => $item->_name, 'value' => $value]
                    , $item->options[self::OPTION_MESSAGES_PARAMS]);

            $validator->setMessagesParams($params);

            // Walidacja
            if (!$validator->isValid($value)) {
                $item->result = false;
                if ($this->options[self::OPTION_BREAK_ON_VALIDATOR_FAILURE] === true) {
                    break;
                }
            }
        }

        return $item->result;
    }

    /**
     * Łączy bieżące opcje walidacji rozszerzając o te podane jako argument funkcji.
     * 
     * @param array $options Parametr łączy (merge) przekazane opcje z domyślnymi opcjami ustawionymi dla bieżącego pola walidacji. <br/>
     *                       Poprzez opcje można ustawić m.in. przerwanie walidacji w momencie wystąpienia błędu walidatora/pola 
     *                       oraz przekazać dodatkowe parametry do komunikatów.
     */
    public function mergeOptions($options) {
        if (!empty($options) && is_array($options)) {
//            $this->options = array_merge($this->options, $options);
            $this->options = \Skinny\ArrayWrapper::deepMerge($this->options, $options);
        }
    }

    /**
     * Alias metody mergeOptions
     * @param array $options
     */
    public function setOptions(array $options) {
        if (!empty($options)) {
            $this->mergeOptions($options);
        }
    }

    /**
     * Funkcja dodająca pojedynczy walidator dla wybranego pola lub całej walidacji.
     * 
     * @param   Validator\ValidatorBase||\Closure $validator 
     *          Parametr może być walidatorem klasy Validator\ValidatorBase lub funkcją (Closure), zwracającą wynik walidacji. <br/>
     *          Jeżeli walidator jest funkcją, przy jej wywołaniu zostanie automatycznie stworzony walidator Validator\Simple obsługujący ten typ walidacji. 
     *          Wewnątrz funkcji można uzywać zmiennej $this, która wskazuje na validator klasy Validator\Simple. <br/>
     *          Wewnątrz funkcji do walidowanej wartości można się odwołać za pomocą <b>$this->value</b>
     * 
     * @param   string|array $errorMsg 
     *          Parametr opisany przy metodzie Validator\ValidatorBase::setUserMessages().

     * @param   array $options 
     *          Parametr opisany przy metodzie validate::_mergeOptions().
     * 
     * @example 
     * <b>1. Stworzenie prostego walidatora przy użyciu domyślnych opcji oraz komunikatów. Poniższy walidator sprawdza czy podana wartość jest tablicą. </b><br/>
     * $validate->add(new Validator\IsArray()); <br/><br/>
     * 
     * <b>2. Stworzenie prostego walidatora wraz z podmianą wyświetlanego komunikatu. </b><br/>
     * $validate->add(new Validator\IsArray(), "Pole '%name%' nie jest tablicą. Bieżąca wartość: '%value%'"); <br/><br/>
     * 
     * Każdy komunikat może zawierać zmienne '%name%' (nazwa walidowanego pola) oraz '%value%' (wartość walidowanego pola), 
     * które będą automatycznie zamieniane na odpowiednie wartości. <br/>
     * Jeżeli '%value%' nie jest stringiem, zostanie automatycznie zparsowane za pomocą funkcji http://php.net/manual/en/function.json-encode.php json_encode <br/><br/>
     * 
     * <b>3. Stworzenie walidatora na podstawie innych klas walidujących np. pochodzących z Zend Framework. </b><br/>
     *  $validate->add(
     *      function(){
     *          $v = new \Zend_Validate_Alnum();
     *          if(!$v->isValid($this->value)) {
     *              $this->setErrors($v->getMessages());
     *              return false;
     *          }
     *      return true;
     *  }); <br/><br/>
     * 
     * Powyższy przykład pokazuje w jaki sposób można użyć zewnętrznego walidatora wraz z nadpisaniem komunikatów tak aby użyć tych pochodzących z Zend Framework. <br/>
     * Komunikaty muszą być tablicą asocjacyjną, gdzie klucz jest identyfikatorem błędu natomiast wartość odpowiednim komunikatem.
     */
    public function add($validator, $errorMsg = null, $options = null) {
        if (!($validator instanceof Validator\ValidatorBase)) {
            $validator = new Validator\Simple($validator);
        }

        if ($validator instanceof Validator\Simple) {
            // przekazanie obecnego poziomu do walidatora simple w celu możliwości 
            // pobrania nazwy pola i dodawania kolejnych podpoziomów (np. przy walidatorach each)
            $validator->item = $this;
        }

        $this->mergeOptions($options);
        $validator->setUserMessages($errorMsg);

        $this->validators[] = $validator;
        return $this;
    }

    /**
     * Jeżeli ustawiono parametr $label ustawia dodatkowy parametr 
     * dla wszystkich walidatorów danego pola.
     * W przeciwnym wypadku zwraca ustawioną wartość lub 
     * pusty string jeśli nie ustawiona.
     * 
     * @param string $label
     * @return \Skinny\Data\Validate|string
     */
    public function label($label = null) {
        if($label === null) {
            // pobranie wartości
            return ($l = @$this->options[Validator\ValidatorBase::OPT_MSG_PARAMS]['label']) !== null ? $l : "";
        } else {
            $this->setOptions([
                Validator\ValidatorBase::OPT_MSG_PARAMS => [
                    'label' => $label
                ]
            ]);
        }

        return $this;
    }

    /**
     * Ustawia parametry wiadomości dla wszystkich walidatorów danego pola
     * @param array $params
     * @return \Skinny\Data\Validate
     */
    public function setMessagesParams(array $params) {
        $this->setOptions([
            Validator\ValidatorBase::OPT_MSG_PARAMS => $params
        ]);

        return $this;
    }

    /**
     * Dodaje wybrany walidator do wielu pól dla danego poziomu walidacji.
     * Działanie analogiczne jak przy metodzie validate::add(), z tym że pierwszym argumentem jest tablica pól.
     * Jeżeli w tablicy pól podany
     * 
     * @param array                         $names      Lista wymaganych pól. <br/>
     *                                                  Może być to zwykła tablica lub tablica asocjacyjna gdzie klucz jest nazwą 
     *                                                  pola a wartość customowym komunikatem o błędzie.
     * @param Validator\ValidatorBase||\Closure  $validator  Parametr opisany przy metodzie validate::add()
     * @param string|array                  $errorMsg   Parametr opisany przy metodzie validate::add()
     * @param array                         $options    Parametr opisany przy metodzie validate::add()
     */
    public function addMultiple($names, $validator, $errorMsg = null, $options = null) {
        if (empty($names) || !is_array($names)) {
            throw new ValidateValidate\Exception('Argument "$names" has to be an array');
        }

        foreach ($names as $key => $value) {
            if (is_string($key)) {
                $this->$key->add(clone $validator, $value, $options);
            } else {
                $this->$value->add(clone $validator, $errorMsg, $options);
            }
        }
    }

    /**
     * Ustawia walidatory dla wszystkich podelementów walidowanego pola.
     * 
     * @param Validator\ValidatorBase||\Closure  $validator
     * @param string|array                  $errorMsg
     * @param array                         $options
     * @return \model\validate
     * 
     * @todo Sprawdzić czy opcje działają OK
     */
    public function each($validator, $errorMsg = null, $options = null) {
        $this->eachValidators[] = [
            'validator' => $validator,
            'errorMsg' => $errorMsg,
            'options' => $options
        ];

        return $this;
    }

    /**
     * Działa analogicznie do metody add z tym, że dodaje walidator na początku.
     * Nie powinien być używany poza klasą validate.
     * 
     * @param Validator\ValidatorBase $validator
     * @param string|array $errorMsg
     * @param array $options
     * @return \model\validate
     */
    private function __prepend($validator, $errorMsg = null, $options = null) {
        if (!($validator instanceof Validator\ValidatorBase)) {
            $validator = new Validator\Simple($validator);
        }

        if ($validator instanceof Validator\Simple) {
            // przekazanie obecnego poziomu do walidatora simple w celu możliwości 
            // pobrania nazwy pola i dodawania kolejnych podpoziomów (np. przy walidatorach each)
            $validator->item = $this;
        }

        $this->mergeOptions($options);
        $validator->setUserMessages($errorMsg);

        array_unshift($this->validators, $validator);

        return $this;
    }

    /**
     * Ustawia wybrane pole jako wymagane. Jest aliasem do walidatora Validator\Required.
     * 
     * @param   string $message Komunikat do nadpisania
     * @return  \model\validate
     * @throws  Validate\Exception
     */
    public function required($message = null) {
//        if ($this->name !== null) {
//            throw new ValidateValidate\Exception("No item selected");
//        }

        $this->__prepend(new Validator\Required(), $message);

        return $this;
    }

    /**
     * Ustawia wybrane pola jako wymagane. 
     * Dla wszystkich wybranych pól wywoływana jest metoda validate::required().
     * 
     * @param   array   $names      Lista wymaganych pól. <br/>
     *                              Może być to zwykła tablica lub tablica asocjacyjna gdzie klucz jest nazwą 
     *                              pola a wartość customowym komunikatem o błędzie.
     * @param   string  $message    Wiadomość customowa dla wszystkich wybranych pól (nadpisywana przez tą ustawioną przez key => value)
     * @return  \model\validate
     * @throws  Validate\Exception
     */
    public function requires($names, $message = null) {
        if (empty($names) || !is_array($names)) {
            throw new ValidateValidate\Exception('Argument "$names" has to be an array');
        }
        foreach ($names as $key => $value) {
            if (is_string($key)) {
                $this->$key->required($value);
            } else {
                $this->$value->required($message);
            }
        }
        return $this;
    }


    
    
    
    
    public function value($value = null) {
        if ($value === null) {
            if ($this->_name) {
                $val = @$val[$this->_name]; // zwraca wartość konkretnego pola

                $data = $this->getRoot()->__allData;
                // Odnalezienie ścieżki danych, które zawsze są aktualne w __allData
                // i zwrócenie odpowiedniego klucza - lub null jeżeli brak wartości
                foreach ($this->__keysFromRoot as $key) {
                    if (!isset($data[$key])) {
                        $data = null;
                        break;
                    } else {
                        $data = $data[$key];
                    }
                }

                return $data;
            } else {
                $val = $this->getRoot()->__allData; // zwraca całość danych ustawionych lokalnie
            }
            return $val;
        } else {
            // Ustawienie wartości dla pola formularza
            // Przy ustawieniu automatycznie merdżujemy __allData
            if ($this->_name) {
                $this->__setAllDataLevelValue($value);
                // tylko poziom ostateczny można ustawiać?
                // 
//                $th = $this;
//                $data = [
//                    $this->name => $value
//                ];
//                while ($th->hasParent()) {
//                    $th = $th->getParent();
//                    if ($th->name) {
//                        $data = [
//                            $th->name => $data
//                        ];
//                    }
//                }
//
//                // Złączenie bieżących danych __allData i nadpisanie ich nową ustawianą wartością
//                $this->getRoot()->__allData = \Skinny\ArrayWrapper::deepMerge($this->getRoot()->__allData, $data);
//                if($this->data instanceof KeyNotExist) {
//                    die('asdasdas');
//                }
//                $this->data[$this->name] = $value;
                // resetuje statusy walidacji
                $this->getRoot()->resetValidation();
            }
        }

        return $this;
    }

    /**
     * Metoda ustawia dane dla danego poziomu z uwzględnieniem ścieżki danych
     * __allData liczonej od korzenia - czyli nadpisujemy jedynie korzeń.
     * 
     * @param mixed $value
     * @throws Validate\Exception
     */
    private function __setAllDataLevelValue($value) {
        if (is_array($value) && !empty($this->items)) {
            throw new Validate\Exception('Cannot set an "array value" for this level');
        }

        $data = $this->getRoot()->__allData;
        $rootData = &$data;
        $prev = "root";
        foreach ($this->__keysFromRoot as $key) {
            if (!isset($data[$key])) {
                $data[$key] = [];
            }
            if (is_string($data)) {
                throw new Validate\Exception("Data for level \"{$prev}\" is invalid");
            }
            $data = &$data[$key];
            $prev = $key;
        }

        if (!empty($this->items)) {
            throw new Validate\Exception("Cannot set non array value for this level");
        }
        $data = $value;
        $this->getRoot()->__allData = $rootData;
    }

    /**
     * Uruchamia walidację dla bieżącego pola (lub całej walidacji).
     * 
     * @param   array $data Metoda wymaga aby walidowane dane były w formie tablicy asocjacyjnej,
     *                      gdzie klucz oznacza nazwę walidowanego pola a wartość - dane do walidacji dla tego pola.
     * @return  boolean
     * @todo Sprawdzenie czy działa walidacja dla pojedynczego pola i podanych danych wejściowych
     */
    public function isValid($data = null) {
        if ($this->status === self::STATUS_VALIDATION_IN_PROGRESS) {
//            throw new ValidateValidate\Exception("Validation is in progress");
        }
        if ($this->status === self::STATUS_NOT_VALIDATED && empty($this->getRoot()->__allData)) {
            throw new Validate\Exception("No data to validate");
        }

        // Jeżeli dany poziom jest już zwalidowany to wystarczy zwrócić wartość
        // Jeżeli chcemy zresetować jej wynik i zwalidować nowe dane, napierw należy
        // wywołać metodę resetValidation()
        if ($this->status === self::STATUS_VALIDATED) {
            return $this->result;
        }

        if (!empty($data)) {
            if (is_array($data) && !$this->hasParent()) {
                foreach($data as $k => $v) {
                    $this->{$k}->__setAllDataLevelValue($v);
                }
                
//                var_dump($this->nazwisko->__keysFromRoot);
//                die();
                
                // jeżeli nie ma rodzica to znaczy że merdżujemy od razu całość danych
//                $this->__allData = \Skinny\ArrayWrapper::deepMerge($this->__allData, $data);
//                
//                var_dump($this->__allData);
//                die();
            } else {
                $this->__setAllDataLevelValue($data);
            }
        }

        $i = 0;
        $result = null;

        // Uruchamianie walidacji niezwalidowanych elementów dopóki istnieje rónica w sumie elementów.
        // Pętla wykona się ponownie np. w momencie gdy dla elementów istnieją walidatory Closure
        // które dodadzą do elementów nowe elementy i walidatory na podstawie walidowanych danych
        // (głównie w przypadku "each")
        do {
            if ($i > 0) {
                // WODZU:
                // teoretycznie można to jeszcze zoptymalizować żeby resetować walidację
                // jedynie na konkretnym poziomie, na którym dokonano przyrostu
                // jest to jednak skomplikowane dla mnie na obecną chwilę, 
                // wszystko działa OK więc zostawiam :)
                $this->resetValidation();
            }
            $itemsBefore = $this->count();
            $result = $this->_validate($this->value());
            $itemsAfter = $this->count();

            $i++;
        } while ($itemsBefore !== $itemsAfter && $i < self::DEEP_VALIDATION_LIMIT);

        if ($i >= self::DEEP_VALIDATION_LIMIT) {
            throw new ValidateValidate\Exception("Deep validation limit reached");
        }

        return $result;
    }

    /**
     * Ustawia status walidacji.
     * 
     * @param string $status
     */
    protected function setStatus($status) {
        $this->status = $status;
    }

    /**
     * Resetuje status oraz result bieżącemu elementowi oraz wszystkim podelementom.
     * Należy również zresetować błędy przygotowane podczas walidacji poszczególnych elementów
     */
    protected function resetValidation() {
        // Przy rozpoczęciu nowej walidacji należy zresetować status
        $this->setStatus(self::STATUS_NOT_VALIDATED);
        $this->result = null;

        $this->resetValidatorsErrors();

        // Jeżeli istnieją jakieś podelementy to również należy im zresetować status
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $item->resetValidation();
                $item->resetValidatorsErrors();
            }
        }
    }

    /**
     * Resetuje tablice błędów dla walidatorów
     */
    protected function resetValidatorsErrors() {
        if (!empty($this->validators)) {
            foreach ($this->validators as $validator) {
                $validator->resetErrors();
            }
        }
    }

    /**
     * Zwraca tablicę błędów, które wystąpiły podczas walidacji.
     * 
     * @param array $errors
     * @return boolean|array
     * @example
     * Jeżeli w jakimś polu wystąpiły błędy, w tablicy zwrotnej pojawi się element (klucz) o nazwie tego pola,
     * do którego przypisana będzie tablica w analogicznej formie: <br/>
     * 
     * ['nazwaPola' => [
     *      '@errors' => [
     *          ['kodBledu' => 'Treść komunikatu o błędzie']
     *          ]
     *      ]
     * ]
     * 
     * Jeżeli przypisano walidator do całości danych (nie podając walidowanego pola) wtedy w tablicy błędów bezpośrednio pojawi się klucz "@errors"
     * 
     * @todo - metoda nie powinna być publiczna - trzeba ją wydzielić osobno a stworzyć publiczną która zwróci już bezpośrednią wartość
     */
    public function getAllErrors(&$errors = []) {
        if (!empty($this->validators)) {
            $errors['@errors'] = [];
            foreach ($this->validators as $validator) {
                if (($e = $validator->getErrors())) {
                    $errors['@errors'] = array_merge($errors['@errors'], $e);
                }
            }
            if (empty($errors['@errors'])) {
                unset($errors['@errors']);
            }
        } else if (empty($this->items)) {
            return false;
        }

        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $errors[$item->_name] = [];
                $item->getAllErrors($errors[$item->_name]);
                if (empty($errors[$item->_name])) {
                    unset($errors[$item->_name]);
                }
            }
        }

        return $errors;
    }

    /**
     * Zwraca tablicę błędów dla bieżącego poziomu (tylko! nie rekurencyjnie)
     * @return array
     */
    public function getErrors() {
        $errors = [];

        if (!empty($this->validators)) {
            foreach ($this->validators as $validator) {
                if (($e = $validator->getErrors())) {
                    $errors = array_merge($errors, $e);
                }
            }
        }

        return $errors;
    }

    /**
     * Sprawdza czy dany poziom był walidowany
     * @return boolean
     */
    public function validated() {
        return $this->status === self::STATUS_VALIDATED;
    }

}

class KeyNotExist {
    
}
