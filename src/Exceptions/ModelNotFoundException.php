<?php

namespace AnasNashat\EasyDev\Exceptions;

use Exception;

class ModelNotFoundException extends Exception
{
    public function __construct(string $message = 'Model not found', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
