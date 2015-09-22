<?php

namespace Skinny\Data\Validator;

/**
 * Klasa bazowa każdego walidatora. 
 * Modeluje podstawowe funkcje niezbędne do walidacji danych wejściowych 
 * przy pomocy metody isValid.
 */
abstract class ValidatorBase {

    /**
     * Flaga określająca czy walidacja dla tego walidatora ma zostać przerwana 
     * w momencie wystąpienia pierwszego błędu czy też ma kontynuować i zwrócić całą listę błędów
     */
    const OPT_BREAK_ON_ERROR = 'optBreakOnError';
    
    /**
     * Parametry do komunikatów (zmienne)
     */
    const OPT_MSG_PARAMS = 'optMessagesParams';

    /**
     * Komunikat w przypadku wystąpienia błędu w polu
     */
    const MSG_INVALID = 'msgInvalid';
    
    /**
     * Klucz parametru do komunikatów o błędach
     * Walidowana wartość
     */
    const PRM_VALUE = '%value%';
    
    /**
     * Klucz parametru do komunikatów o błędach
     * Nazwa walidowanego pola (USTAWIANA AUTOMATYCZNIE POPRZEZ Validate - w przeciwnym wypdku trzeba ją ustawić)
     */
    const PRM_NAME = '%name%';

    /**
     * Przechowuje szablony komunikatów dla zainstniałych błędów w postaci klucz (kod błędu) => wartość.
     * @var array
     */
    protected $_messagesTemplates = [
        self::MSG_INVALID => 'Wartość jest nieprawidłowa'
    ];

    /**
     * Ustawianie za pomocą metody validator::setUserMessages()
     * @var string|array 
     */
    protected $_userMessages = null;

    /**
     * Tablica opcji dla walidatora.
     * @var array
     */
    protected $_options = [
        self::OPT_BREAK_ON_ERROR => true
    ];

    /**
     * Przechowuje listę komunikatów o błędach, które wystąpiły podczas walidacji. <br/>
     * Komunikaty są odpowiednio wybrane na podstawie szablonów komunikatów <br/>
     * i są postaci klucz (kod błędu) => wartość. Po wywołaniu funkcji validator::_parseErrors() <br/>
     * komunikaty są parsowane i wszystkie zmienne przyjmują odpowiednie wartości na podstawie danych zawartych w $_messagesParams.
     * 
     * @var array
     */
    public $_errors = [];

    /**
     * Tablica parametrów, które są używane przy parsowaniu komunikatów o błędach.
     * 
     * @var array
     */
    protected $_messagesParams = [];

    /**
     * Konstruktor walidatora
     * @param array|null $options Tablica ustawień dla walidatora
     */
    public function __construct($options = null) {
        $this->_mergeOptions($options);
        
        if($options !== null && isset($options[self::OPT_MSG_PARAMS])) {
            $this->setMessagesParams($options[self::OPT_MSG_PARAMS]);
        }
    }

    /**
     * Dodaje błąd do tablicy błędów na podstawie istniejących szablonów gdzie priorytetem są szablony customowe ustawione przez użytkownika.
     * 
     * @param string $key Klucz do błędu znajdujący się w zdefiniowanych komunikatach - jeżeli klucz nie istnieje dodawany jest błąd ogólny.
     */
    public function error($key = self::MSG_INVALID) {
        
        if (isset($this->_userMessages) && is_string($this->_userMessages)) {
            $this->_errors[$key] = $this->_userMessages;
        } else if (isset($this->_userMessages[$key])) {
            $this->_errors[$key] = $this->_userMessages[$key];
        } else {
            $this->_errors[$key] = $this->_messagesTemplates[$key];
        }
        return false;
    }

    /**
     * Parsuje wszystkie błędy podstawiając wartości z parametrów wiadomości. <br/>
     * Zmienne mogą mieć wielką literę jeśli potrzeba - wystarczy użyć %^name%
     * 
     * @todo dokładna dokumentacja!
     */
    private function _parseErrors() {
        if (!empty($this->_errors)) {
            foreach ($this->_errors as &$error) {
                if (is_string($error) && !empty($this->_messagesParams)) {
                    foreach ($this->_messagesParams as $param => $value) {
                        $param = str_replace('%', '', $param);
                        while (false !== ($pos = strpos($error, "%$param%"))) {
                            $val = $value;
                            if ($error[$pos - 1] == '^' && is_string($value)) {
                                $val = ucfirst($value);
                                $error = substr_replace($error, null, $pos - 1, 1);
                                $pos--;
                            }

                            if (!is_string($val)) {
                                $val = json_encode($val);
                            }

                            $error = substr_replace($error, $val, $pos, strlen($param) + 2);
                        }
                    }
                }
            }
        }
    }

    /**
     * Ustawia szablony komunikatów dla klasy
     * 
     * @param array $messages
     */
    protected function _setMessagesTemplates(array $messages) {
        $this->_messagesTemplates = array_merge($this->_messagesTemplates, $messages);
    }

    /**
     * Ustawia komunikaty użytkownika.
     * 
     * @param   string|array $messages Własne komunikaty o błędach. <br/>
     *          Jeżeli parametr jest stringiem, ten komunikat zostanie użyty w momencie gdy dane nie przejdą walidacji (niezależnie od kodu błędu). <br/>
     *          Może być również tablicą asocjacyjną gdzie klucz jest kodem błędu a wartość odpowiednim komunikatem z tablicy szablonów wiadomości klasy walidującej. <br/>
     *          Do odpowiednich kodów błędów można się odwołać poprzez stałe klasy walidującej (np. validate\isString::NOT_STRING).
     */
    public function setUserMessages($messages) {
        $this->_userMessages = $messages;
    }

    /**
     * Ustawia parametry do późniejszego parsowania komunikatów o błędach.
     * 
     * @param array $params
     */
    public function setMessagesParams($params) {
        if (!empty($params) && is_array($params)) {
            $this->_messagesParams = array_merge($this->_messagesParams, $params);
        }
    }

    /**
     * Zwraca listę błędów w postaci: <br/>
     * 
     * [
     *      pole1 => [
     *          'errors' => [
     *              0 => ['tooLong' => '"pole1" jest za długie']
     *              ...
     *          ]
     *      ]
     *      ...
     * ]
     * 
     * @return array
     */
    public function getErrors() {
        $this->_parseErrors();
        return $this->_errors;
    }

    /**
     * Uruchomienie walidacji dla danych wejściowych.
     * 
     * @param mixed $value
     */
    public function isValid($value) {
        $this->setValueParam($value);
        return true;
    }
    
    /**
     * Ustawia wartość jako parametr do komunikatów.
     * 
     * @param mixed $value
     */
    public function setValueParam($value) {
        $this->setMessagesParams([
            self::PRM_VALUE => $value
        ]);
    }

    /**
     * Łączy bieżące opcje rozszerzając je o te podane jako argument.
     * 
     * @param array $options
     */
    protected function _mergeOptions($options) {
        if(is_array($options)) {
            $this->_options = array_merge($this->_options, $options);
        }
    }

    /**
     * Resetuje tablicę błędów
     */
    public function resetErrors() {
        $this->_errors = [];
    }

}
