<?php

namespace Skinny;

/**
 * Description of Location
 *
 * @author Daro
 */
class Location {

    protected static $_isRemote;

    /**
     * Stwierdza, czy aplikacja została uruchomiona zdalnie, np. poprzez przeglądarkę WWW, bota, zdalny harmonogram zadań (np. cron).
     * Zwróci false, gdy aplikacja została uruchomiona przez lokalny skrypt, z linii poleceń lub lokalny harmonogram zadań (np. cron).
     * @return boolean
     */
    public static function isRemote() {
        if (!isset(self::$_isRemote)) {
            $sapi = php_sapi_name();
            self::$_isRemote = $sapi == 'cli' || substr($sapi, 0, 3) === 'cgi';
        }
        return self::$_isRemote;
    }

    /**
     * Przekierowuje przeglądarkę na podany adres URL z podanymi parametrami.
     * Jeżeli URL nie zostanie podany (null) zostanie użyty bieżący.
     * Ostatnim parametrem jest kod przekierowania HTTP (domyślnie 302 Found).
     * @param string $url [opcjonalny] URL przekierowania
     * @param array $params [opcjonalny] parametry do dopisania do URL
     * @param integer $responseCode [opcjonalny] kod odpowiedzi HTTP - domyślnie 302 Found
     */
    public static function redirect($url = null, array $params = array(), $responseCode = 302) {
        self::_redirect(null, $url, $params, $responseCode);
    }

    /**
     * Przekierowuje przeglądarkę na podany adres URL z podanymi parametrami.
     * Wymusza użycie protokołu HTTP.
     * Jeżeli URL nie zostanie podany (null) zostanie użyty bieżący.
     * Ostatnim parametrem jest kod przekierowania HTTP (domyślnie 302 Found).
     * @param string $url [opcjonalny] URL przekierowania
     * @param array $params [opcjonalny] parametry do dopisania do URL
     * @param integer $responseCode [opcjonalny] kod odpowiedzi HTTP - domyślnie 302 Found
     */
    public static function redirectHttp($url = null, array $params = array(), $responseCode = 302) {
        self::_redirect('http://', $url, $params, $responseCode);
    }

    /**
     * Przekierowuje przeglądarkę na podany adres URL z podanymi parametrami.
     * Wymusza użycie protokołu HTTPS.
     * Jeżeli URL nie zostanie podany (null) zostanie użyty bieżący.
     * Ostatnim parametrem jest kod przekierowania HTTP (domyślnie 302 Found).
     * @param string $url [opcjonalny] URL przekierowania
     * @param array $params [opcjonalny] parametry do dopisania do URL
     * @param integer $responseCode [opcjonalny] kod odpowiedzi HTTP - domyślnie 302 Found
     */
    public static function redirectHttps($url = null, array $params = array(), $responseCode = 302) {
        self::_redirect('https://', $url, $params, $responseCode);
    }

    /**
     * Wewnętrzna metoda realizująca przekierowania.
     * Jeżeli protokół nie zostanie podany, pobierany jest w pierwszej kolejności z URL, w drugiej z aktualnego żądania.
     * @param string $protocol [opcjonalny] protokół HTTP ('http://') lub HTTPS ('https://') doklejany na początku URL
     * @param string $url [opcjonalny] URL przekierowania
     * @param array $params [opcjonalny] parametry do dopisania do URL
     * @param integer $responseCode [opcjonalny] kod odpowiedzi HTTP - domyślnie 302 Found
     */
    protected static function _redirect($protocol = null, $url = null, array $params = array(), $responseCode = 302) {
        if (null === $protocol) {
            // TODO: pobranie protokołu z URL, a jeśli nie występuje, to poniższa linijka
            $protocol = self::getProtocol();
        }
        if (preg_match('/^(https?|ftp):\/\//', $url, $matches)) {
            // TODO: wycięcię z URL protokołu, jeżeli istnieje - URL ma być postaci: "/*", np. "/", "/tekst", "/abc/xyz"
        }
        $url = self::_checkUrl($url);
        if (!empty($params))
            $url = self::_addParams($url, $params);
        self::sendHeader('Location: ' . Url::combine($protocol, $url), true, $responseCode);
        exit();
    }

    /**
     * Stwierdza, czy żądanie do aplikacji zostało wysłane przez protokół HTTP.
     * @return boolean
     */
    public static function isHttp() {
        return self::isRemote() && !self::isHttps();
    }

    /**
     * Stwierdza, czy żądanie do aplikacji zostało wysłane przez protokół HTTPS.
     * @return boolean
     */
    public static function isHttps() {
        return self::isRemote() && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off";
    }

    /**
     * Konwertuje podany URL do formy bezwzględnej ustawiając protokół HTTP.
     * Używa URL aktualnego żądania, gdy URL nie zostanie podany.
     * @param string $url URL do konwersji
     * @return string
     */
    public static function getHttp($url = null) {
        // TODO: obsługa protokołu wbudowanego na początku URL
        // TODO: wykorzystanie w _redirect() i ogólna integracja z pozostałymi metodami
        $url = $url ? $url : $_SERVER['REQUEST_URI'];
        $url = "http://" . $_SERVER['SERVER_NAME'] . $url;
        return $url;
    }

    /**
     * Konwertuje podany URL do formy bezwzględnej ustawiając protokół HTTPS.
     * Używa URL aktualnego żądania, gdy URL nie zostanie podany.
     * @param string $url URL do konwersji
     * @return string
     */
    public static function getHttps($url = null) {
        // TODO: obsługa protokołu wbudowanego na początku URL
        // TODO: wykorzystanie w _redirect() i ogólna integracja z pozostałymi metodami
        $url = $url ? $url : $_SERVER['REQUEST_URI'];
        $url = "https://" . $_SERVER['SERVER_NAME'] . $url;
        return $url;
    }

    /**
     * Pobiera aktualnie używany protokół gotowy do doklejenia z przodu URL.
     * W przypadku, gdy nie jest używany ani HTTP ani HTTPS, HTTP zostanie przyjęty za domyślny.
     * @return string
     */
    public static function getProtocol() {
        if (self::isHttps())
            return 'https://';
        return 'http://';
    }

    /**
     * stwierdza, czy URL ma poprawną formę.
     * @param string $url
     * @return boolean
     */
    public static function isURL($url) {
        // TODO: sprawdzić poprawność sprawdzenia
        return (bool) preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $url);
    }

    /**
     * Sprawdza, czy URL jest prawidłowy i w razie potrzeby generuje wyjątek.
     * Jeżeli URL nie zostanie podany, zostanie pobrany z aktualnego żądania.
     * @param string $url URL do sprawdzenia
     * @return string URL po ewentualnych filtrach
     * @throws UriException URL nie przejdzie kontroli
     */
    protected static function _checkUrl($url) {
        $url = $url ? : $_SERVER['REQUEST_URI'];
        if (empty($url))
            throw new UriException('Redirect URL is not specified.');
        // TODO: sprawdzenie poprawności URL
        return $url;
    }

    /**
     * Dodaje paramtry do URL w formacie /klucz1/wartość1/klucz2/wartość2/...
     * @param string $url URL, do którego mają być dodane parametry
     * @param array $params tablica parametrów klucz-wartość
     * @return string
     */
    public static function _addParams($url, array $params) {
        array_walk($params, function ($key, $value) use (&$url) {
                    $url .= "/$key/$value";
                });
        return $url;
    }

    /**
     * Stwierdza, czy nagłówki HTTP zostały już wysłane. Jeżeli parametr zostanie ustawiony na true,
     * zostanie wygenerowany wyjątek w przypadku, gdy nagłówki zostały już wysłane.
     * @param boolean $throw [opcjonalny] czy ma zostac wygenerowany wujątek, gdy nagłówki zostały już wysłane
     * @return boolean
     * @throws \RuntimeException
     */
    public static function areHeadersSent($throw = false) {
        $sent = headers_sent($file, $line);
        if ($sent && $throw)
            throw new \RuntimeException('Cannot send headers, headers already sent in ' . $file . ', line ' . $line);
        return !$sent;
    }

    /**
     * Wysyła podany nagłówek z opcjonalnym kodem.
     * @param Response\Http\Header\HeaderInterface|string $header treść nagłówka lub obiekt nagłówka
     * @param boolean $replace [opcjonalny] czy nagłówek ma nadpisać podobny, ustawiony wcześniej - domyślnie tak
     * @param ineteger $responseCode [opcjonalny] kod odpowiedzi HTTP
     */
    public static function sendHeader($header, $replace = true, $responseCode = null) {
        self::areHeadersSent(true);
        if ($header instanceof Response\Http\Header\HeaderInterface) {
            $code = $header->getCode();
            $header = $header->toString();
        }
        if (empty($code))
            $code = $responseCode;
        header($header, $replace, $code);
    }

}