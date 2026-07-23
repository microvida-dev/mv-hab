<?php

namespace App\Policies;

use App\Models\AnnualDocumentUpdateRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class AnnualDocumentUpdateRequestPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->viewAnyBackoffice($user);
    }

    public function view(User $user, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): bool
    {
        if ($user->hasRole('candidate')) {
            return $annualDocumentUpdateRequest->user_id === $user->id;
        }

        return $this->viewBackoffice($user, $annualDocumentUpdateRequest);
    }

    public function create(User $user): bool
    {
        return $this->createBackoffice($user);
    }

    public function update(User $user, AnnualDocumentUpdateRequest $annualDocumentUpdateRequest): bool
    {
        if ($user->hasRole('candidate')) {
            return $annualDocumentUpdateRequest->user_id === $user->id;
        }

        return $this->approveBackoffice($user, $annualDocumentUpdateRequest)
            || $this->rejectBackoffice($user, $annualDocumentUpdateRequest);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'documents', 'view');
    }

    public function viewBackoffice(
        User $user,
        AnnualDocumentUpdateRequest $annualDocumentUpdateRequest,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsAnnualDocumentUpdateRequest(
                $user,
                $annualDocumentUpdateRequest,
            );
    }

    public function createBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'create');
    }

    public function approveBackoffice(
        User $user,
        AnnualDocumentUpdateRequest $annualDocumentUpdateRequest,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'approve')
            && $this->municipalScope->ownsAnnualDocumentUpdateRequest(
                $user,
                $annualDocumentUpdateRequest,
            );
    }

    public function rejectBackoffice(
        User $user,
        AnnualDocumentUpdateRequest $annualDocumentUpdateRequest,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'reject')
            && $this->municipalScope->ownsAnnualDocumentUpdateRequest(
                $user,
                $annualDocumentUpdateRequest,
            );
    }
}
