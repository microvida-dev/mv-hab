<?php

namespace App\Exceptions;

use App\Enums\AccessDenialReason;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

class AccessDeniedException extends AuthorizationException
{
    /**
     * @param  array<string, bool|float|int|string|null>  $safeContext
     */
    public function __construct(
        public readonly AccessDenialReason $reason,
        private readonly array $safeContext = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $reason->publicMessage(),
            $reason->publicCode(),
            $previous,
        );
    }

    public function httpStatus(): int
    {
        return 403;
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    public function safeContext(): array
    {
        return $this->safeContext;
    }
}
