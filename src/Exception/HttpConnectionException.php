<?php namespace WebSMS\Exception;

use Exception;

class HttpConnectionException extends Exception
{
    /**
     * HttpConnectionException constructor.
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message, int $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}