<?php

namespace App\Policies;

use App\Enums\DefinitiveListStatus;
use App\Models\DefinitiveList;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class DefinitiveListPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'public_lists', 'view');
    }

    public function viewBackoffice(User $user, DefinitiveList $list): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsDefinitiveList($user, $list);
    }

    public function generateAnyBackoffice(User $user): bool
    {
        return $this->canGenerateBackoffice($user);
    }

    public function generateBackoffice(User $user): bool
    {
        return $this->canGenerateBackoffice($user);
    }

    public function reviewBackoffice(User $user, DefinitiveList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'review');
    }

    public function approveBackoffice(User $user, DefinitiveList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'approve');
    }

    public function publishBackoffice(User $user, DefinitiveList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'publish');
    }

    public function lockBackoffice(User $user, DefinitiveList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'lock');
    }

    public function archiveBackoffice(User $user, DefinitiveList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'archive');
    }

    private function canGenerateBackoffice(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $user->municipality_id !== null
            && $this->canAccess($user, 'public_lists', 'generate');
    }

    private function canMutateBackoffice(
        User $user,
        DefinitiveList $list,
        string $action,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'public_lists', $action)
            && $this->municipalScope->ownsDefinitiveList($user, $list);
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
