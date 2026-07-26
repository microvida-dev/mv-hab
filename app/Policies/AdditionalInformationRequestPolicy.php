<?php

namespace App\Policies;

use App\Models\AdditionalInformationRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class AdditionalInformationRequestPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, AdditionalInformationRequest $request): bool
    {
        return $user->hasRole('candidate')
            ? $request->user_id === $user->id && $this->canAccess($user, 'complaints', 'view')
            : $this->canAccess($user, 'complaints', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'update');
    }

    public function update(User $user, AdditionalInformationRequest $request): bool
    {
        return $this->create($user);
    }

    public function viewBackoffice(User $user, AdditionalInformationRequest $request): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'complaints', 'view')
            && $this->municipalScope->ownsAdditionalInformationRequest($user, $request);
    }

    public function closeBackoffice(User $user, AdditionalInformationRequest $request): bool
    {
        return $this->canMutateBackoffice($user, $request, 'close');
    }

    public function markOverdueBackoffice(User $user, AdditionalInformationRequest $request): bool
    {
        return $this->canMutateBackoffice($user, $request, 'mark_overdue');
    }

    private function canMutateBackoffice(
        User $user,
        AdditionalInformationRequest $request,
        string $action,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'complaints', $action)
            && $this->municipalScope->ownsAdditionalInformationRequest($user, $request);
    }
}
