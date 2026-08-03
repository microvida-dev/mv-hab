<?php

namespace App\Policies;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class NotificationPreferencePolicy
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function view(User $user, NotificationPreference $preference): bool
    {
        return $preference->user_id === $user->id || $user->hasPermissionTo('notifications', 'view');
    }

    public function update(User $user, NotificationPreference $preference): bool
    {
        return $preference->user_id === $user->id || (! $user->hasRole(['candidate', 'auditor']) && $user->hasPermissionTo('notifications', 'update'));
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('notification_preferences.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }
}
