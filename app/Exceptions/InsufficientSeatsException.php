<?php

namespace App\Exceptions;

use Exception;

class InsufficientSeatsException extends Exception
{
    public function __construct(string $message = 'Insufficient seats available')
    {
        parent::__construct($message);
    }
}
