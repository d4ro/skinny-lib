<?php

namespace Skinny;

/**
 * Standard Skinny exception class.
 * It is base class for all other Skinny exceptions.
 *
 * @author Daro
 */
class Exception extends \Exception {

    protected $_related;

    public function __construct($message = null, $code = null, $previous = null, $related = null) {
        parent::__construct($message, $code, $previous);
        $this->_related = $related;
    }

    public function getRelated() {
        return $this->_related;
    }

    /**
     * Throws exception if condition is fulfilled.
     * If exception is not provided it will be created new instance of Skinny standard exception class.
     * @param boolean $condition condition to check
     * @param string|Exception $exception instance of PHP Exception class or text message for new one
     * @throws Exception
     */
    public static function throwIf($condition, $exception = null) {
        if ($condition) {
            if (is_string($exception)) {
                $exception = new static($exception);
            }

            if (!($exception instanceof \Exception)) {
                $exception = new static();
            }

            throw $exception;
        }
    }

}
