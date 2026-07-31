<?php

namespace App\Policies;

use App\Models\ApplicationReviewBatch;
use App\Models\Contest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ApplicationReviewBatchPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(
        User $user,
        ApplicationReviewBatch $batch,
    ): bool {
        return $this->viewAny($user)
            && $this->municipalScope->ownsContest(
                $user,
                $batch->contest,
            );
    }

    public function sealForContest(User $user, Contest $contest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsContest($user, $contest);
    }
}
