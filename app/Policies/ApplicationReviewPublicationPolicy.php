<?php

namespace App\Policies;

use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewPublication;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ApplicationReviewPublicationPolicy
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
        ApplicationReviewPublication $publication,
    ): bool {
        return $this->viewAny($user)
            && $this->municipalScope->ownsContest(
                $user,
                $publication->contest,
            );
    }

    public function publishForBatch(
        User $user,
        ApplicationReviewBatch $batch,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'publish')
            && $this->municipalScope->ownsContest($user, $batch->contest);
    }
}
