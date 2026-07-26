<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ApplicationPolicy
{
    use ChecksPermissions;

    private const MODULE = 'applications';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function view(User $user, Application $application): bool
    {
        if ($user->hasRole('candidate')) {
            return $application->user_id === $user->id
                && $this->canAccess($user, self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->ownsApplication($user, $application);
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view');
    }

    public function viewBackoffice(User $user, Application $application): bool
    {
        return ! $user->hasRole('candidate')
            && (
                $this->canAccess($user, self::MODULE, 'view')
                || $this->canAccess($user, 'documents', 'view')
            )
            && $this->municipalScope->ownsApplication($user, $application);
    }

    public function analyzeDocumentsBackoffice(User $user, Application $application): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'documents', 'analyze')
            && $this->municipalScope->ownsApplication($user, $application);
    }

    public function updateBackoffice(User $user, Application $application): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsApplication($user, $application);
    }

    public function auditBackoffice(User $user, Application $application): bool
    {
        return ! $user->hasRole('candidate')
            && $this->municipalScope->ownsApplication($user, $application)
            && (
                $this->canAccess($user, self::MODULE, 'audit')
                || $this->canAccess($user, self::MODULE, 'view')
            );
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, Application $application): bool
    {
        return $application->user_id === $user->id
            && $application->isEditable()
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function submit(User $user, Application $application): bool
    {
        return $this->update($user, $application);
    }

    public function withdraw(User $user, Application $application): bool
    {
        return $application->user_id === $user->id
            && $application->status->canBeWithdrawn()
            && $this->canAccess($user, self::MODULE, 'update');
    }

    public function viewReceipt(User $user, Application $application): bool
    {
        return $this->view($user, $application)
            && $application->application_number !== null;
    }
}
