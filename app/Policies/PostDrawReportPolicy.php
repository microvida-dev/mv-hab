<?php

namespace App\Policies;

use App\Models\PostDrawReport;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class PostDrawReportPolicy
{
    use ChecksPermissions;

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
}
