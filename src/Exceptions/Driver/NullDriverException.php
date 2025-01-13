<?php

namespace Convenia\Pigeon\Exceptions\Driver;

use Exception;
use Throwable;

class NullDriverException extends Exception
{
    public function __construct($message = 'Cannot use [NULL] driver', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
