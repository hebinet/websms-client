<?php namespace WebSms\Exception;

use Exception;

class ApiException extends Exception
{
    /**
     * ApiException constructor.
     *
     * @param $message
     * @param $code
     */
    public function __construct($message, $code)
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