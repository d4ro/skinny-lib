<?php

namespace Skinny\Data;

/**
 * Klasa validate jest klasą umożliwiającą walidację danych wejściowych 
 * za pomocą istniejących walidatorów lub stworzonych przez siebie.
 */
class Validate extends \Skinny\DataObject\ObjectModelBase {

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
     * Przechowuje kolejne klucze (name) od korzenia do danego poziomu.
     * Używane przy pobieraniu wartości bieżącego pola (po to aby dobierać się
     * do __allData za pomocą pętli).
     * 
     * @var array
     */
    protected $_keysFromRoot = [];

    /**
     * Tablica przechowująca wszystkie walidatory przypisane do bieżącego pola.
     * @var array
     */
    protected $_validators = [];

    /**
     * Tutaj są przechowywane walidatory dla wszystkich podwartości walidowanego zakresu danych.
     * Wszystkie walidatory each muszą być najpierw ustawione przed wywołaniem 
     * docelowej metody isValid - żeby ustawić te walidatory musimy wiedzieć z jakimi danymi
     * mamy do czynienia.
     * 
     * @var array
     */
    protected $_eachValidators = [];

    /**
     * Przechowuje komunikaty o zaistniałych błędach dla bieżącego pola. Komunikaty są generowane w momencie wywołania metody validate::getErrors().
     * @var array
     */
//    protected $errors = []; // unused??

    /**
     * Przechowuje ustawienia dla bieżącego pola.
     * @var array
     */
    protected $_options = [
        self::OPTION_BREAK_ON_ITEM_FAILURE => false,
        self::OPTION_BREAK_ON_VALIDATOR_FAILURE => true,
        self::OPTION_MESSAGES_PARAMS => []
    ];

    /**
     * Bieżący status walidacji
     * @var string
     */
    protected $_status = self::STATUS_NOT_VALIDATED;

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
//    public $data = null; czy potrzebne??

    /**
     * Przechowuje wynik walidacji
     * @var boolean
     */
    protected $_result = null;

    /**
     * Konstruktor
     */
    public function __construct() {
        
    }

    /**
     * Odczyt nieistniejącej właściwości - tworzy nowy obiekt tej klasy oraz kopiuje do niego opcje z bieżącego poziomu.
     * 
     * @param string $name Nazwa pola do walidacji
     * @return Validate
     */
    public function &__get($name) {
        $new = !isset($this->_items[$name]);

        $item = parent::__get($name);

        if ($new) {
            $item->mergeOptions($this->_options);

            // Budowanie kluczy od roota tak aby był do nich szybki dostęp (do danych)
            $item->_keysFromRoot = array_merge([], $this->_keysFromRoot);
            $item->_keysFromRoot[] = $name;
        }

        return $item;
    }

    /**
     * Domyślnie generowany nowy obiekt
     * 
     * @return \self
     */
    protected function _createObject() {
        return new self();
    }

    /**
     * Zapis do nieistniejącej właściwości
     * 
     * @param string    $name   Nazwa pola
     * @param self      $value  Przypisywana wartość
     * @throws Validate\Exception
     */
    public function __set($name, $value) {
        if (!($value instanceof self)) {
            throw new Validate\Exception("Invalid value");
        }
        parent::__set($name, $value);
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
        if ($this->_status === self::STATUS_VALIDATION_IN_PROGRESS) {
            // throw new Validate\Exception("Validation is in progress");
        } else if ($this->_status === self::STATUS_VALIDATED) {
            /**
             * Jeżeli ten walidator został juz zwalidowany, należy zwrócić wynik walidacji.
             * Taka sutuacja wystąpi w momencie gdy wywołano najpierw metodę isValid podając 
             * tablicę do walidacji a następnie wywołano tą metodę bez podania argumentów. 
             * Przy podaniu argumentu do funkcji isValid wynik oraz status walidacji jest resetowany.
             */
            return $this->_result;
        }

        // Ustawienie statusu walidacji
        $this->setStatus(self::STATUS_VALIDATION_IN_PROGRESS);

        // Ustawienie wartości do walidacji
        $toCheck = $this->__setupToCheckValue($value);

        // Ustawienie bieżącego poziomu danych do walidacji
//        $this->_data = $toCheck; ?? Czy potrzebne ??

        $this->_result = true;
        if (!empty($this->_validators) || !empty($this->_eachValidators)) {
            $this->__prepareLevelValidation($toCheck); // przygotowanie walidacji każdego poziomu - m.in. "each"
            $this->_result = $this->_validateItem($this, $toCheck);
        } else if (empty($this->_items)) {
//            throw new Validate\Exception("No items to validate");
            // brak walidatorów oznacza poprawną walidację
        }

        /**
         * Jeżeli istnieją podelementy tego pola i ustawiono sprawdzenie rekursywne 
         * oraz wynik walidacji dla bieżącego pola jest pozytywny (lub ustawiono flagę, aby nie przerywać walidacji)
         * należy zwalidować wszystkie podelementy
         */
        if (!empty($this->_items) && $this->_result === true) {
            foreach ($this->_items as $item) {
                if (!$item->_validate($toCheck)) {
                    $this->_result = false;
                    if ($this->_options[self::OPTION_BREAK_ON_ITEM_FAILURE] === true) {
                        break;
                    }
                }
            }
        }

        $this->setStatus(self::STATUS_VALIDATED);
        return $this->_result;
    }

    /**
     * Funkcja zliczająca sumę elementów i podelementów dla danego poziomu
     * 
     * @return int
     */
//    public function countValidators() {
//        $count = count($this->_validators) + count($this->_eachValidators);
//        if (!empty($this->_items)) {
//            foreach ($this->_items as $item) {
//                $count += $item->countValidators();
//            }
//        }
//
//        return $count;
//    }

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
        if (!empty($this->_eachValidators) && !empty($data) && !($data instanceof KeyNotExist) && (is_array($data) || $data instanceof \Traversable)) {
            // jeżeli ustawiono walidatory dla wszystkich podelementów to należy je najpierw przygotować
            foreach ($data as $k => $v) {
                foreach ($this->_eachValidators as $vData) {
                    $this->$k->__prepend($vData['validator'], $vData['errorMsg'], $vData['options']);
                    $this->$k->mergeOptions($vData['options']); // TODO czy to na pewno tak ma być = opcje nadpisywane na poziomie każdego walidatora z osobna...
                }
            }

            $this->_eachValidators = [];
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
        $item->_result = true;

        // Jeżeli nie jest ustawiony walidator Required a wartość jest pusta
        // nie musimy przeprowadzać walidacji
        if (
                !$this->hasValidator(new Validator\Required()) &&
                false === (new Validator\NotEmpty())->isValid($value)
        ) {
            return true;
        }

        foreach ($item->_validators as $validator) {
            // Ustawienie customowych komunikatów wraz z przekazaniem name oraz value
            $params = array_merge(
                    ['name' => $item->_name, 'value' => $value]
                    , $item->_options[self::OPTION_MESSAGES_PARAMS]);

            $validator->setMessagesParams($params);

            // Walidacja
            if (!$validator->isValid($value)) {
                $item->_result = false;
                if ($this->_options[self::OPTION_BREAK_ON_VALIDATOR_FAILURE] === true) {
                    break;
                }
            }
        }

        return $item->_result;
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
//            $this->_options = array_merge($this->_options, $options);
            $this->_options = \Skinny\DataObject\ArrayWrapper::deepMerge($this->_options, $options);
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
            $validator->parent = $this->parent();
            $validator->root = $this->root();
        }

        $this->mergeOptions($options);
        $validator->setUserMessages($errorMsg);

        $this->_validators[] = $validator;
        return $this;
    }

    /**
     * Ustawia lub pobiera ustawiony parametr dla komunikatów "label"
     * (opcja OPT_MSG_PARAMS dla klucza "label")
     * 
     * @param string $label
     * @return \Skinny\Data\Validate|string
     */
    public function label($label = null) {
        if ($label === null) {
            // pobranie wartości
            return ($l = @$this->_options[Validator\ValidatorBase::OPT_MSG_PARAMS]['label']) !== null ? $l : "";
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
            throw new Validate\Exception('Argument "$names" has to be an array');
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
     * @param Validator\ValidatorBase|\Closure  $validator  Obiekt walidatora
     * @param string|array                      $errorMsg   Komunikat w przypadku błędu
     * @param array                             $options    Tablica ustawień walidatorów
     * @return \model\validate
     * 
     * @todo Sprawdzić czy nadpisywanie opcji działa poprawnie
     */
    public function each($validator, $errorMsg = null, $options = null) {
        $this->_eachValidators[] = [
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

        array_unshift($this->_validators, $validator);

        return $this;
    }

    /**
     * Ustawia wymagalność istnienia wybranego klucza w walidowanej 
     * tablicy/obiekcie danych.
     * 
     * @param   string $message Komunikat w przypadku błędu
     * @return  \model\validate
     */
    public function mustExist($message = null) {
        return $this->__prepend(new Validator\MustExist(), $message);
    }

    /**
     * Ustawia wybrane pole jako wymagane.
     * Automatycznie dodaje 2 walidatory MustExist oraz NotEmpty.
     * 
     * @param   string $message Komunikat w przypadku błędu
     * @return  \model\validate
     */
    public function required($message = null) {
        return $this->__prepend(new Validator\Required(), $message);
    }

    /**
     * Ustawia wybrane pola jako wymagane. 
     * Dla wszystkich wybranych pól wywoływana jest metoda validate::required().
     * 
     * @param   array   $names      Lista wymaganych pól. <br/>
     *                              Może być to zwykła tablica lub tablica 
     *                              asocjacyjna gdzie klucz jest nazwą 
     *                              pola a wartość customowym komunikatem o błędzie.
     * @param   string  $message    Komunikat w przypadku błędu
     * @return  \model\validate
     * @throws  Validate\Exception
     */
    public function requires($names, $message = null) {
        if (empty($names) || !is_array($names)) {
            throw new Validate\Exception('Argument "$names" has to be an array');
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

    /**
     * Sprawdza czy istnieje dla tego poziomu walidator/y podanego typu.
     * Sprawdza instanceof element. W przypadku tablicy obiektów wszystkie
     * walidatory muszą istnieć żeby spełnić warunek.
     * 
     * @param Validator|Array[Validator] $validator Obiekt klasy Validator lub tablica obiektów
     * @return boolean
     */
    public function hasValidator($validator) {
        $validators = $validator;
        if (!is_array($validator)) {
            $validators = [$validator];
        }
        $toFind = count($validators);
        $found = 0;

        if (!empty($this->_validators)) {
            foreach ($validators as $validatorToFind) {
                foreach ($this->_validators as $v) {
                    $className = get_class($validatorToFind);
                    if ($v instanceof $className) {
                        if (++$found >= $toFind) {
                            return true;
                        }

                        break;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Ustawienie lub pobranie wartości dla wybranego poziomu.
     * 
     * @param mixed $value
     * @return mixed
     */
    public function value($value = null) {
        if ($value === null) {
            if ($this->_name) {
                $val = @$val[$this->_name]; // zwraca wartość konkretnego pola

                $data = $this->root()->__allData;
                // Odnalezienie ścieżki danych, które zawsze są aktualne w __allData
                // i zwrócenie odpowiedniego klucza - lub null jeżeli brak wartości
                foreach ($this->_keysFromRoot as $key) {
                    if (!isset($data[$key])) {
                        $data = null;
                        break;
                    } else {
                        $data = $data[$key];
                    }
                }

                return $data;
            } else {
                $val = $this->root()->__allData; // zwraca całość danych ustawionych lokalnie
            }
            return $val;
        } else {
            // Ustawienie wartości dla pola
            // Przy ustawieniu automatycznie merdżujemy __allData
            if ($this->_name) {
                $this->__setAllDataLevelValue($value);

                // resetuje statusy walidacji
                $this->root()->resetValidation();
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
        if (is_array($value) && !empty($this->_items)) {
            throw new Validate\Exception('Cannot set an "array value" for this level');
        }

        $data = $this->root()->__allData;
        $rootData = &$data;
        $prev = "root";
        foreach ($this->_keysFromRoot as $key) {
            if (!isset($data[$key])) {
                $data[$key] = [];
            }
            if (is_string($data)) {
                throw new Validate\Exception("Data for level \"{$prev}\" is invalid");
            }
            $data = &$data[$key];
            $prev = $key;
        }

        if (!empty($this->_items)) {
            throw new Validate\Exception("Cannot set non array value for this level");
        }
        $data = $value;
        $this->root()->__allData = $rootData;
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
        if ($this->_status === self::STATUS_VALIDATION_IN_PROGRESS) {
//            throw new Validate\Exception("Validation is in progress");
        }

        // Jeżeli wszystkie dane są puste - niewypełnione a walidacja nie została przeprowadzona - błąd
        if ($this->_status === self::STATUS_NOT_VALIDATED && empty($this->root()->__allData) && $data === null) {
            throw new Validate\Exception("No data to validate");
        }

        // Jeżeli dany poziom jest już zwalidowany to wystarczy zwrócić wartość
        // Jeżeli chcemy zresetować jej wynik i zwalidować nowe dane, napierw należy
        // wywołać metodę resetValidation()
        if ($this->_status === self::STATUS_VALIDATED) {
            return $this->_result;
        }

        $this->setData($data);

        $i = 0;
        $result = null;

        // Uruchamianie walidacji niezwalidowanych elementów dopóki istnieje rónica w sumie elementów.
        // Pętla wykona się ponownie np. w momencie gdy dla elementów istnieją walidatory Closure
        // które dodadzą do elementów nowe elementy i walidatory na podstawie walidowanych danych
        // (głównie w przypadku "each")
        do {
            if ($i > 0) {
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
            throw new Validate\Exception("Deep validation limit reached");
        }

        return $result;
    }
    
    /**
     * Ustawia (nadpisuje) dane dla wybranego poziomu.
     * @param mixed $data
     * @return \Skinny\Data\Validate
     */
    public function setData($data = null) {
        if ($data !== null) {
            if (is_array($data) && $this->isRoot()) {
                foreach ($data as $k => $v) {
                    $this->{$k}->__setAllDataLevelValue($v);
                }
            } else {
                $this->__setAllDataLevelValue($data);
            }
        }
        return $this;
    }

    /**
     * Ustawia status walidacji.
     * 
     * @param string $status
     */
    protected function setStatus($status) {
        $this->_status = $status;
    }

    /**
     * Resetuje status oraz result bieżącemu elementowi oraz wszystkim podelementom.
     * Należy również zresetować błędy przygotowane podczas walidacji poszczególnych elementów
     */
    protected function resetValidation() {
        // Przy rozpoczęciu nowej walidacji należy zresetować status
        $this->setStatus(self::STATUS_NOT_VALIDATED);
        $this->_result = null;

        $this->resetValidatorsErrors();

        // Jeżeli istnieją jakieś podelementy to również należy im zresetować status
        if (!empty($this->_items)) {
            foreach ($this->_items as $item) {
                $item->resetValidation();
                $item->resetValidatorsErrors();
            }
        }
    }

    /**
     * Resetuje tablice błędów dla walidatorów
     */
    protected function resetValidatorsErrors() {
        if (!empty($this->_validators)) {
            foreach ($this->_validators as $validator) {
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
        if (!empty($this->_validators)) {
            $errors['@errors'] = [];
            foreach ($this->_validators as $validator) {
                if (($e = $validator->getErrors())) {
                    $errors['@errors'] = array_merge($errors['@errors'], $e);
                }
            }
            if (empty($errors['@errors'])) {
                unset($errors['@errors']);
            }
        } else if (empty($this->_items)) {
            return false;
        }

        if (!empty($this->_items)) {
            foreach ($this->_items as $item) {
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

        if (!empty($this->_validators)) {
            foreach ($this->_validators as $validator) {
                if (($e = $validator->getErrors())) {
                    $errors = array_merge($errors, $e);
                }
            }
        }

        return $errors;
    }

    /**
     * Zwraca pierwszy komunikat błędu dla bieżącego poziomu
     * lub null jeśli nie ma błędów.
     * 
     * @return string|null
     */
    public function getFirstError() {
        $errors = $this->getErrors();
        if (!empty($errors)) {
            foreach ($errors as $message) {
                return $message;
            }
        }

        return null;
    }

    /**
     * Sprawdza czy dany poziom był walidowany.
     * 
     * @return boolean
     */
    public function validated() {
        return $this->_status === self::STATUS_VALIDATED;
    }

    /**
     * Sprawdza czy obiekt był już walidowany i czy zawiera błędy.
     * 
     * @return boolean
     */
    public function hasErrors() {
        return $this->validated() && !$this->isValid();
    }

    /**
     * Zwraca aktualnie ustawione dane dla całego obiektu.
     * 
     * @return array
     */
    public function getData() {
        return $this->root()->__allData;
    }

}

class KeyNotExist {
    
}
