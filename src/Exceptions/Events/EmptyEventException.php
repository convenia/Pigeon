<?php

namespace Convenia\Pigeon\Exceptions\Events;

use Exception;
use Throwable;

class EmptyEventException extends Exception
{
    public function __construct($message = 'Cannot emmit empty event', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}