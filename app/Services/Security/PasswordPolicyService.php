<?php

namespace App\Services\Security;

class PasswordPolicyService
{
    /**
     * @return array{
     *     minimum_length: int,
     *     must_use_password_manager: bool,
     *     mfa_required_for_backoffice: bool,
     *     status: string,
     *     note: string
     * }
     */
    public function recommendations(): array
    {
        return [
            'minimum_length' => 12,
            'must_use_password_manager' => true,
            'mfa_required_for_backoffice' => true,
            'status' => 'DEMO — SUJEITO A VALIDAÇÃO DO MUNICÍPIO/DPO',
            'note' => 'A política é recomendação operacional; não bloqueia utilizadores existentes nesta sprint.',
        ];
    }
}
