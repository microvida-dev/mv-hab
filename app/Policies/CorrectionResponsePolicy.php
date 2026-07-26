<?php

namespace App\Policies;

use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class CorrectionResponsePolicy
{
    use ChecksPermissions;

    private const MODULE = 'administrative_processes';

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function view(User $user, CorrectionResponse $correctionResponse): bool
    {
        if ($user->hasRole('candidate')) {
            return $correctionResponse->user_id === $user->id
                && $this->canAccess($user, self::MODULE, 'view');
        }

        return $this->canAccess($user, self::MODULE, 'view');
    }

    public function create(User $user, CorrectionRequest $correctionRequest): bool
    {
        return $user->hasRole('candidate')
            && $correctionRequest->user_id === $user->id
            && $correctionRequest->isOpenForCandidateResponse()
            && $this->canAccess($user, self::MODULE, 'create');
    }

    public function review(User $user, CorrectionResponse $correctionResponse): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, self::MODULE, 'approve');
    }

    public function viewBackoffice(User $user, CorrectionResponse $response): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, self::MODULE, 'view')
            && $this->municipalScope->ownsCorrectionResponse($user, $response);
    }

    public function decideBackoffice(User $user, CorrectionResponse $response): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, self::MODULE, 'decide')
            && $this->municipalScope->ownsCorrectionResponse($user, $response);
    }
}
