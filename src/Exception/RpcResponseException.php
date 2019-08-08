<?php

namespace Convenia\AMQP\Exceptions;

use Exception;
use Throwable;

class RpcResponseException extends Exception
{
    public $custom_code;

    public $body;

    public function __construct($message = '', $code = 0, Throwable $previous = null, $body = [], $customCode = 0)
    {
        $this->body = $body;
        $this->custom_code = $customCode;
        parent::__construct($message, $code, $previous);
    }
}
