<?php

namespace App\Policies;

use App\Models\NotificationEventRule;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class NotificationEventRulePolicy
{
    use ChecksCommunicationAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function view(User $user, NotificationEventRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function update(User $user, NotificationEventRule $rule): bool
    {
        return $this->canManageCommunications($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('notification_event_rules.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission('notification_event_rules.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        NotificationEventRule $rule,
    ): bool {
        return $user->hasPermission('notification_event_rules.update')
            && $this->municipalScope->canMutateNotificationEventRule(
                $user,
                $rule,
            );
    }

    public function activateBackoffice(
        User $user,
        NotificationEventRule $rule,
    ): bool {
        return $user->hasPermission('notification_event_rules.activate')
            && $this->municipalScope->canMutateNotificationEventRule(
                $user,
                $rule,
            );
    }

    public function deactivateBackoffice(
        User $user,
        NotificationEventRule $rule,
    ): bool {
        return $user->hasPermission('notification_event_rules.deactivate')
            && $this->municipalScope->canMutateNotificationEventRule(
                $user,
                $rule,
            );
    }
}
