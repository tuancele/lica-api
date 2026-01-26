<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidReservationException extends Exception
{
    protected $code = 422;
}
