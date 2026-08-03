<?php

namespace App\Policies;

use App\Models\CorrectionRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class CorrectionRequestPolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function viewRevalidationQueue(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function view(User $user, CorrectionRequest $correctionRequest): bool
    {
        if ($user->hasRole('candidate')) {
            return $correctionRequest->user_id === $user->id
                && $correctionRequest->isVisibleToCandidate()
                && $this->canAccess($user, self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function submit(
        User $user,
        CorrectionRequest $correctionRequest,
    ): bool {
        return $user->hasRole('candidate')
            && (int) $correctionRequest->user_id
                === (int) $user->id
            && $correctionRequest->isVisibleToCandidate()
            && $this->canAccess(
                $user,
                self::MODULE,
                'create',
            );
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'create');
    }

    public function update(User $user, CorrectionRequest $correctionRequest): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'update');
    }

    public function viewBackoffice(User $user, CorrectionRequest $request): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->ownsCorrectionRequest($user, $request);
    }

    public function updateBackoffice(User $user, CorrectionRequest $request): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsCorrectionRequest($user, $request);
    }

    public function issueBackoffice(User $user, CorrectionRequest $request): bool
    {
        return $this->transitionBackoffice($user, $request, 'issue');
    }

    public function cancelBackoffice(User $user, CorrectionRequest $request): bool
    {
        return $this->transitionBackoffice($user, $request, 'cancel');
    }

    public function completeBackoffice(User $user, CorrectionRequest $request): bool
    {
        return $this->transitionBackoffice($user, $request, 'complete');
    }

    public function markOverdueBackoffice(User $user, CorrectionRequest $request): bool
    {
        return $this->transitionBackoffice($user, $request, 'mark_overdue');
    }

    public function extendDeadlineBackoffice(
        User $user,
        CorrectionRequest $request,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsCorrectionRequest(
                $user,
                $request,
            );
    }

    public function startRevalidationBackoffice(
        User $user,
        CorrectionRequest $request,
    ): bool {
        return $this->canManageRevalidation($user, $request);
    }

    public function previewRevalidationBackoffice(
        User $user,
        CorrectionRequest $request,
    ): bool {
        return $this->canManageRevalidation($user, $request);
    }

    public function sealRevalidationBackoffice(
        User $user,
        CorrectionRequest $request,
    ): bool {
        return $this->canManageRevalidation($user, $request);
    }

    private function transitionBackoffice(
        User $user,
        CorrectionRequest $request,
        string $action,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, $action)
            && $this->municipalScope->ownsCorrectionRequest($user, $request);
    }

    private function canManageRevalidation(
        User $user,
        CorrectionRequest $request,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'update')
            && $this->municipalScope->ownsCorrectionRequest($user, $request);
    }
}
