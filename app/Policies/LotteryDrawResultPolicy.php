<?php

namespace App\Policies;

use App\Models\LotteryDrawResult;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class LotteryDrawResultPolicy
{
    use ChecksPermissions;

    public function view(User $user, LotteryDrawResult $result): bool
    {
        return $this->canAccess($user, 'allocations', 'view');
    }
}
