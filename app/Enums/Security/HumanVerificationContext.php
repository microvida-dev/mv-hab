<?php

declare(strict_types=1);

namespace App\Enums\Security;

enum HumanVerificationContext: string
{
    case Login = 'login';
    case PublicVisit = 'public_visit';

    public function configPrefix(): string
    {
        return match ($this) {
            self::Login => 'turnstile',
            self::PublicVisit => 'public_visits.turnstile',
        };
    }

    public function defaultAction(): string
    {
        return match ($this) {
            self::Login => 'login',
            self::PublicVisit => 'public_visit',
        };
    }
}
