<?php

namespace App\Policies;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Policies\Concerns\ChecksCommunicationAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class NotificationTemplatePolicy
{
    use ChecksCommunicationAccess;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewCommunications($user);
    }

    public function view(User $user, NotificationTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCommunications($user);
    }

    public function update(User $user, NotificationTemplate $template): bool
    {
        return $this->canManageCommunications($user);
    }

    public function approve(User $user, NotificationTemplate $template): bool
    {
        return $this->canPublishCommunications($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('notification_templates.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        NotificationTemplate $template,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsNotificationTemplate(
                $user,
                $template,
            );
    }

    public function createBackoffice(
        User $user,
        ?NotificationTemplate $template = null,
    ): bool {
        if ($template instanceof NotificationTemplate) {
            return $user->hasPermission(
                'notification_template_versions.create',
            ) && $this->municipalScope->canMutateNotificationTemplate(
                $user,
                $template,
            );
        }

        return $user->hasPermission('notification_templates.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function updateBackoffice(
        User $user,
        NotificationTemplate $template,
    ): bool {
        return $user->hasPermission('notification_templates.update')
            && $this->municipalScope->canMutateNotificationTemplate(
                $user,
                $template,
            );
    }

    public function archiveBackoffice(
        User $user,
        NotificationTemplate $template,
    ): bool {
        return $user->hasPermission('notification_templates.archive')
            && $this->municipalScope->canMutateNotificationTemplate(
                $user,
                $template,
            );
    }

    public function previewBackoffice(
        User $user,
        NotificationTemplate $template,
    ): bool {
        return $user->hasPermission('notification_templates.preview')
            && $this->municipalScope->ownsNotificationTemplate(
                $user,
                $template,
            );
    }
}
