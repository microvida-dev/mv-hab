<?php

namespace App\Policies;

use App\Models\LotteryDraw;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class LotteryDrawPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'allocations', 'view');
    }

    public function view(User $user, LotteryDraw $draw): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'create');
    }

    public function update(User $user, LotteryDraw $draw): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'update');
    }

    public function approve(User $user, LotteryDraw $draw): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'allocations', 'approve');
    }

    public function audit(User $user, LotteryDraw $draw): bool
    {
        return $this->canAccess($user, 'allocations', 'audit') || $user->hasRole('auditor');
    }
}
