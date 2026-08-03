<?php

namespace App\Data\Program53;

use App\Enums\Program53FailureCode;
use App\Enums\Program53FailureDisposition;

final readonly class Program53Failure
{
    public function __construct(
        public Program53FailureCode $code,
        public Program53FailureDisposition $disposition,
    ) {}

    public function retryable(): bool
    {
        return $this->disposition === Program53FailureDisposition::Retryable;
    }

    public function safeMessage(): string
    {
        return $this->code->safeMessage();
    }
}
