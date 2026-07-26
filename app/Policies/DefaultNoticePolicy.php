<?php

namespace App\Policies;

use App\Models\DefaultNotice;
use App\Models\User;
use App\Policies\Concerns\ChecksFinanceAccess;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DefaultNoticePolicy
{
    use ChecksFinanceAccess;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canViewFinance($user);
    }

    public function view(User $user, DefaultNotice $defaultNotice): bool
    {
        return $user->hasRole('candidate')
            ? $defaultNotice->candidate_visible && $this->ownsFinanceRecord($user, $defaultNotice) && $this->canViewFinance($user)
            : $this->canViewFinance($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateFinance($user);
    }

    public function update(User $user, DefaultNotice $defaultNotice): bool
    {
        return $this->canManageFinance($user);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'finance', 'default_notices.view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, DefaultNotice $notice): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsDefaultNotice($user, $notice);
    }

    public function createBackoffice(User $user): bool
    {
        return $this->canMutateBackoffice($user, 'default_notices.create')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function issueBackoffice(User $user, DefaultNotice $notice): bool
    {
        return $this->canMutateBackoffice($user, 'default_notices.issue')
            && $this->municipalScope->ownsDefaultNotice($user, $notice);
    }

    public function cancelBackoffice(User $user, DefaultNotice $notice): bool
    {
        return $this->canMutateBackoffice($user, 'default_notices.cancel')
            && $this->municipalScope->ownsDefaultNotice($user, $notice);
    }

    private function canMutateBackoffice(User $user, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'finance', $action);
    }
}
