<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait HandlesSecurityAccess
{
    protected function backoffice(User $user, string $action = 'view'): bool
    {
        return ! $user->hasRole('candidate')
            && ($user->hasPermission("settings.{$action}") || $user->hasPermission("audit_logs.{$action}") || $user->hasPermission("privacy.{$action}"));
    }

    protected function audit(User $user): bool
    {
        return ! $user->hasRole('candidate') && ($user->hasPermission('audit_logs.view') || $user->hasPermission('audit_logs.audit'));
    }

    protected function privacy(User $user, string $action = 'view'): bool
    {
        return ! $user->hasRole('candidate') && $user->hasPermission("privacy.{$action}");
    }
}
