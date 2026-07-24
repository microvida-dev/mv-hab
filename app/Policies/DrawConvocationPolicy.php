<?php

namespace App\Policies;

use App\Models\DrawConvocation;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DrawConvocationPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate')
            ? $this->canAccess($user, 'notifications', 'view')
            : $this->canAccess($user, 'notifications', 'view');
    }

    public function view(User $user, DrawConvocation $convocation): bool
    {
        if ($user->hasRole('candidate')) {
            return $convocation->user_id === $user->id && $this->canAccess($user, 'notifications', 'view');
        }

        return $this->canAccess($user, 'notifications', 'view');
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'create');
    }

    public function update(User $user, DrawConvocation $convocation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'notifications', 'update');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'lotteries', 'view');
    }

    public function viewBackoffice(User $user, DrawConvocation $convocation): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsDrawConvocation($user, $convocation);
    }

    public function sendBackoffice(User $user, DrawConvocation $convocation): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'lotteries', 'convocations.send')
            && $this->municipalScope->ownsDrawConvocation($user, $convocation);
    }
}
