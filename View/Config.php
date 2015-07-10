<?php

namespace Skinny\View;

/**
 * @property string $baseUrl Ścieżka główna aplikacji
 * @property string $jsPath Ścieżka do plików JavaScript
 * @property string $jsExtension Rozszerzenie plików JavaScript
 * @property string $cssPath Ścieżka do plików CSS
 * @property string $cssExtension Rozszerzenie plików CSS
 */
class Config extends \Skinny\Store {
    public function __construct($obj = null) {
        // Domyślna konfiguracja
        $this->baseUrl = '/';
        
        // domyślne rozszerzenia
        $this->jsExtension = '.js';
        $this->cssExtension = '.css';
        
        parent::__construct($obj);
    }
}