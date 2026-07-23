<?php

namespace App\Policies;

use App\Models\RankingSnapshot;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RankingSnapshotPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, RankingSnapshot $snapshot): bool
    {
        return $this->viewAny($user);
    }

    public function lock(User $user, RankingSnapshot $snapshot): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'approve');
    }

    public function archive(User $user, RankingSnapshot $snapshot): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'update');
    }

    public function export(User $user, RankingSnapshot $snapshot): bool
    {
        return $this->canAccess($user, 'scoring', 'export');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'scoring', 'view');
    }

    public function viewBackoffice(User $user, RankingSnapshot $snapshot): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsRankingSnapshot($user, $snapshot);
    }

    public function lockBackoffice(User $user, RankingSnapshot $snapshot): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'lock')
            && $this->municipalScope->ownsRankingSnapshot($user, $snapshot);
    }

    public function archiveBackoffice(User $user, RankingSnapshot $snapshot): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'archive')
            && $this->municipalScope->ownsRankingSnapshot($user, $snapshot);
    }
}
