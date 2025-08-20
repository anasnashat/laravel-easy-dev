<?php

namespace AnasNashat\EasyDev\Exceptions;

use Exception;

class RelationAlreadyExistsException extends Exception
{
    public function __construct(string $message = 'Relation already exists', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
