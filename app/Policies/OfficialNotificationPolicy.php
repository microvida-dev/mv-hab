<?php

namespace App\Policies;

use App\Models\OfficialNotification;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class OfficialNotificationPolicy
{
    use ChecksPermissions;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'notifications', 'view');
    }

    public function view(User $user, OfficialNotification $notification): bool
    {
        return $user->hasRole('candidate')
            ? $notification->user_id === $user->id && $this->canAccess($user, 'notifications', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'create');
    }

    public function update(User $user, OfficialNotification $notification): bool
    {
        return $notification->user_id === $user->id || (! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'update'));
    }

    public function acknowledge(User $user, OfficialNotification $notification): bool
    {
        return $notification->user_id === $user->id && $notification->requires_acknowledgement;
    }

    public function archive(User $user, OfficialNotification $notification): bool
    {
        return $notification->user_id === $user->id || (! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'update'));
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->hasPermission('notifications.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        OfficialNotification $notification,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsOfficialNotification(
                $user,
                $notification,
            );
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->hasPermission('notifications.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function markSentBackoffice(
        User $user,
        OfficialNotification $notification,
    ): bool {
        return $user->hasPermission('notifications.mark_sent')
            && $this->municipalScope->ownsOfficialNotification(
                $user,
                $notification,
            );
    }

    public function markFailedBackoffice(
        User $user,
        OfficialNotification $notification,
    ): bool {
        return $user->hasPermission('notifications.mark_failed')
            && $this->municipalScope->ownsOfficialNotification(
                $user,
                $notification,
            );
    }
}
