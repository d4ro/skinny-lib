<?php

namespace Skinny\View;

/**
 * @property string $applicationPath Ścieżka bezwzględna do katalogu public aplikacji
 * @property string $baseUrl Baseurl aplikacji
 * 
 * @property string $jsPath Ścieżka do plików JavaScript
 * @property string $jsExtension Rozszerzenie plików JavaScript
 * 
 * @property string $cssPath Ścieżka do plików CSS
 * @property string $cssExtension Rozszerzenie plików CSS
 * 
 * @property string $layoutsPath Ścieżka do plików layoutu
 * @property string $layout Plik layoutu - może być to ścieżka względna/bezwzględna lub url
 * 
 * @property string $templatesExtension Rozszerzenie plików layoutu oraz widoków
 * 
 * @property boolean $isRenderAllowed Czy widok ma być domyślnie renderowany
 */
class Config extends \Skinny\Store {

    public function __construct($obj = null) {
        // Domyślna konfiguracja
        $this->applicationPath = getcwd();
        $this->baseUrl = '/';
        $this->jsExtension = '.js';
        $this->cssExtension = '.css';

        // Czy widok ma być domyślnie renderowany przy pomocy ustawionego renderera
        $this->isRenderAllowed = true;

        $this->templatesExtension = '.tpl';

        parent::__construct($obj);
    }

}
