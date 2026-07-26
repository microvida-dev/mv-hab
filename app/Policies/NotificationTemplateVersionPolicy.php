<?php

namespace App\Policies;

use App\Models\NotificationTemplateVersion;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class NotificationTemplateVersionPolicy
{
    use ChecksCommunicationAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function view(User $user, NotificationTemplateVersion $version): bool
    {
        return $this->canViewCommunications($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function update(User $user, NotificationTemplateVersion $version): bool
    {
        return $this->canManageCommunications($user);
    }

    public function approve(User $user, NotificationTemplateVersion $version): bool
    {
        return $this->canPublishCommunications($user);
    }

    public function viewBackoffice(
        User $user,
        NotificationTemplateVersion $version,
    ): bool {
        return $user->hasPermission('notification_template_versions.view')
            && $this->municipalScope->ownsNotificationTemplateVersion(
                $user,
                $version,
            );
    }

    public function approveBackoffice(
        User $user,
        NotificationTemplateVersion $version,
    ): bool {
        return $user->hasPermission('notification_template_versions.approve')
            && $this->municipalScope->canMutateNotificationTemplateVersion(
                $user,
                $version,
            );
    }

    public function activateBackoffice(
        User $user,
        NotificationTemplateVersion $version,
    ): bool {
        return $user->hasPermission('notification_template_versions.activate')
            && $this->municipalScope->canMutateNotificationTemplateVersion(
                $user,
                $version,
            );
    }

    public function archiveBackoffice(
        User $user,
        NotificationTemplateVersion $version,
    ): bool {
        return $user->hasPermission('notification_template_versions.archive')
            && $this->municipalScope->canMutateNotificationTemplateVersion(
                $user,
                $version,
            );
    }
}
