<?php

namespace App\Policies;

use App\Models\PostDrawReport;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class PostDrawReportPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'reports', 'view');
    }

    public function view(User $user, PostDrawReport $report): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'reports', 'create');
    }

    public function viewBackoffice(User $user, PostDrawReport $report): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'lotteries', 'view')
            && $this->municipalScope->ownsPostDrawReport($user, $report);
    }

    public function exportBackoffice(User $user, PostDrawReport $report): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'lotteries', 'export')
            && $this->municipalScope->ownsPostDrawReport($user, $report);
    }
}
