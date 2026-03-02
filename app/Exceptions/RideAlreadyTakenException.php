<?php

namespace App\Exceptions;

use Exception;

class RideAlreadyTakenException extends Exception
{
    public function __construct(string $message = 'This ride has already been accepted by another driver')
    {
        parent::__construct($message);
    }
}
