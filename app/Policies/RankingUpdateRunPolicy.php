<?php

namespace App\Policies;

use App\Models\LotteryDraw;
use App\Models\RankingUpdateRun;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class RankingUpdateRunPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'scoring', 'view');
    }

    public function view(User $user, RankingUpdateRun $run): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'scoring', 'update');
    }

    public function createBackoffice(User $user, LotteryDraw $draw): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'scoring', 'run')
            && $this->municipalScope->ownsLotteryDraw($user, $draw);
    }
}
