<?php namespace WebSms\Exception;

use Exception;

class AuthorizationFailedException extends Exception
{

    /**
     * AuthorizationFailedException constructor.
     *
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message, int $code = 401)
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