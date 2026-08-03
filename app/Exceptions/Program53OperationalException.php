<?php

namespace App\Exceptions;

use App\Enums\Program53FailureCode;
use RuntimeException;

final class Program53OperationalException extends RuntimeException
{
    public function __construct(public readonly Program53FailureCode $failureCode)
    {
        parent::__construct($failureCode->safeMessage());
    }
}
