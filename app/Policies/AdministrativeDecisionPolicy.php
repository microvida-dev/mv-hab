<?php

namespace App\Policies;

use App\Models\AdministrativeDecision;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class AdministrativeDecisionPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_decisions';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, AdministrativeDecision $administrativeDecision): bool
    {
        if ($user->hasRole('candidate')) {
            $application = $administrativeDecision->application;

            return $application instanceof Application
                && $application->user_id === $user->id
                && $administrativeDecision->candidate_visible
                && $this->canAccess($user, 'administrative_processes', 'view');
        }

        return $this->viewBackoffice($user, $administrativeDecision);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function approve(User $user, AdministrativeDecision $administrativeDecision): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'approve');
    }

    public function viewBackoffice(User $user, AdministrativeDecision $decision): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->ownsAdministrativeDecision($user, $decision);
    }

    public function createBackoffice(User $user, AdministrativeProcess $process): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'create')
            && ! $process->isClosed()
            && $this->municipalScope->ownsAdministrativeProcess($user, $process);
    }

    public function approveBackoffice(User $user, AdministrativeDecision $decision): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'approve')
            && $this->municipalScope->ownsAdministrativeDecision($user, $decision);
    }

    public function cancelBackoffice(User $user, AdministrativeDecision $decision): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'cancel')
            && $this->municipalScope->ownsAdministrativeDecision($user, $decision);
    }
}
