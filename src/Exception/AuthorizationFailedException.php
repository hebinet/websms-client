<?php

namespace WebSms\Exception;

use Exception;

class AuthorizationFailedException extends Exception
{
    public function __construct(string $message, int $code = 401)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__.": [{$this->code}]: {$this->message}\n";
    }
}
