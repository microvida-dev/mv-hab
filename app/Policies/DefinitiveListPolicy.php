<?php

namespace App\Policies;

use App\Enums\DefinitiveListStatus;
use App\Models\DefinitiveList;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class DefinitiveListPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'public_lists', 'view');
    }

    public function view(User $user, DefinitiveList $list): bool
    {
        if ($user->hasRole('candidate')) {
            return $list->entries()->where('user_id', $user->id)->exists()
                && $this->statusIsIn($list, [DefinitiveListStatus::Published, DefinitiveListStatus::Locked])
                && $this->canAccess($user, 'public_lists', 'view');
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'public_lists', 'create');
    }

    public function update(User $user, DefinitiveList $list): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->status($list) !== DefinitiveListStatus::Locked
            && $this->canAccess($user, 'public_lists', 'update');
    }

    public function approve(User $user, DefinitiveList $list): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'public_lists', 'approve');
    }

    public function publish(User $user, DefinitiveList $list): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'public_lists', 'publish');
    }

    /** @param  list<DefinitiveListStatus>  $statuses */
    private function statusIsIn(DefinitiveList $list, array $statuses): bool
    {
        $status = $this->status($list);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function status(DefinitiveList $list): ?DefinitiveListStatus
    {
        $status = $list->getAttribute('status');

        if ($status instanceof DefinitiveListStatus) {
            return $status;
        }

        return is_string($status) ? DefinitiveListStatus::tryFrom($status) : null;
    }
}
