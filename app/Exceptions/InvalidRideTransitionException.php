<?php

namespace App\Exceptions;

use Exception;

class InvalidRideTransitionException extends Exception
{
    public function __construct(string $message = 'Invalid ride status transition')
    {
        parent::__construct($message);
    }
}
