<?php

namespace App\Policies;

use App\Models\InspectionChecklistTemplate;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;

class InspectionChecklistTemplatePolicy
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function view(
        User $user,
        InspectionChecklistTemplate $template,
    ): bool {
        return $this->viewBackoffice($user, $template);
    }

    public function create(User $user): bool
    {
        return $this->createBackoffice($user);
    }

    public function update(
        User $user,
        InspectionChecklistTemplate $template,
    ): bool {
        return $this->updateBackoffice($user, $template);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'inspections.templates.view',
        ) && $this->municipalScope
            ->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(
        User $user,
        InspectionChecklistTemplate $template,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope
                ->ownsInspectionChecklistTemplate(
                    $user,
                    $template,
                );
    }

    public function createBackoffice(User $user): bool
    {
        return $user->hasPermission(
            'inspections.templates.create',
        ) && $user->municipality_id !== null;
    }

    public function updateBackoffice(
        User $user,
        InspectionChecklistTemplate $template,
    ): bool {
        return $user->hasPermission(
            'inspections.templates.update',
        ) && $this->municipalScope
            ->canMutateInspectionChecklistTemplate(
                $user,
                $template,
            );
    }
}
