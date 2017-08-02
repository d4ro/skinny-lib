<?php

namespace Skinny\Application\Response\Http\Header;

/**
 * Header response type
 *
 * @author Mario <mwintoch@confido.pl>
 */
class ContentType extends HeaderBase {

    /**
     * Constructor
     *
     * @param string $contentType
     */
    public function __construct($contentType) {
        parent::__construct();

        $this->_name  = 'Content-Type';
        $this->_value = $contentType;
    }

    /**
     * Factory for JSON content type,
     * most used in AJAX
     *
     * @return ContentType
     */
    public static function JSON() {
        return new ContentType('application/json');
    }

    /**
     * Factory for text plain content type
     *
     * @return ContentType
     */
    public static function PLAIN() {
        return new ContentType('text/plain');
    }

    /**
     * Factory for text event stream content type,
     * used in Server-Sent Events (SSE)
     *
     * @return ContentType
     */
    public static function EVENT_STREAM() {
        return new ContentType('text/event-stream');
    }

}
