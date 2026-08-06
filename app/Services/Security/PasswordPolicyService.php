<?php

namespace App\Services\Security;

use Illuminate\Validation\Rules\Password;

final class PasswordPolicyService
{
    public const MIN_LENGTH = 12;

    public const MAX_LENGTH = 128;

    public function rule(): Password
    {
        return Password::min(self::MIN_LENGTH)
            ->max(self::MAX_LENGTH)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    /**
     * @return array{
     *     minimum_length: int,
     *     maximum_length: int,
     *     must_use_password_manager: bool,
     *     mfa_required_for_backoffice: bool,
     *     status: string,
     *     note: string
     * }
     */
    public function recommendations(): array
    {
        return [
            'minimum_length' => self::MIN_LENGTH,
            'maximum_length' => self::MAX_LENGTH,
            'must_use_password_manager' => true,
            'mfa_required_for_backoffice' => true,
            'status' => 'ATIVA',
            'note' => 'A política é aplicada a novos registos e a alterações ou redefinições de palavra-passe.',
        ];
    }
}
