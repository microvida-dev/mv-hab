<?php

namespace App\Policies;

use App\Models\AdministrativeProcess;
use App\Models\ApplicationReview;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ApplicationReviewPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, ApplicationReview $applicationReview): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->ownsApplicationReview($user, $applicationReview);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function createForProcess(User $user, AdministrativeProcess $process): bool
    {
        return $this->create($user)
            && $this->municipalScope->ownsAdministrativeProcess($user, $process);
    }

    public function update(User $user, ApplicationReview $applicationReview): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsApplicationReview($user, $applicationReview);
    }
}
