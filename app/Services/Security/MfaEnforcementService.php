<?php

namespace App\Services\Security;

use App\Models\User;

class MfaEnforcementService
{
    private const SENSITIVE_ROLES = [
        'administrator',
        'municipal_technician',
        'jury',
        'financial_manager',
        'maintenance_manager',
        'auditor',
    ];

    public function requiresMfa(?User $user): bool
    {
        return $user !== null && $user->hasRole(self::SENSITIVE_ROLES);
    }

    public function hasConfirmedDevice(User $user): bool
    {
        return $user->mfaDevices()
            ->whereNotNull('confirmed_at')
            ->whereNull('disabled_at')
            ->exists();
    }

    public function sessionVerified(): bool
    {
        return (bool) session('mfa.verified_at')
            && now()->diffInMinutes(session('mfa.verified_at')) <= 480;
    }

    public function markVerified(): void
    {
        session(['mfa.verified_at' => now()]);
    }

    public function forgetVerification(): void
    {
        session()->forget('mfa.verified_at');
    }
}
