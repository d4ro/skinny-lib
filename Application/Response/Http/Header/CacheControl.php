<?php

namespace Skinny\Application\Response\Http\Header;

/**
 * Header cache control
 *
 * @author Mario <mwintoch@confido.pl>
 */
class CacheControl extends HeaderBase {

    /**
     * Constructor
     *
     * @param string $cacheControl
     */
    public function __construct($cacheControl) {
        parent::__construct();

        $this->_name = 'Cache-Control';
        $this->_value = $cacheControl;
    }

    /**
     * Factory for "no-cache" content type,
     * most used in AJAX
     *
     * @return CacheControl
     */
    public static function NO_CACHE() {
        return new CacheControl('no-cache');
    }
}
