<?php

namespace Skinny\View;

interface RendererInterface {
    
    /**
     * Renderowanie widoku do html'a.
     * 
     * @param string $template  Ścieżka do renderowanego pliku szablonu.
     * @param array $params     Tablica asocjacyjna parametrów, które mają być przekazane
     *                          do widoku.
     */
    public function fetch($template, $params);
    
}
