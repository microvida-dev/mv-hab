<?php

namespace App\Policies;

use App\Enums\ProvisionalListStatus;
use App\Models\ProvisionalList;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ProvisionalListPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'public_lists', 'view');
    }

    public function view(User $user, ProvisionalList $list): bool
    {
        if ($user->hasRole('candidate')) {
            return $list->entries()->where('user_id', $user->id)->exists()
                && $this->status($list) !== ProvisionalListStatus::Draft
                && $this->canAccess($user, 'public_lists', 'view');
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'public_lists', 'create');
    }

    public function update(User $user, ProvisionalList $list): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->statusIsIn($list, [ProvisionalListStatus::Draft, ProvisionalListStatus::UnderReview, ProvisionalListStatus::Published, ProvisionalListStatus::ComplaintPeriodOpen])
            && $this->canAccess($user, 'public_lists', 'update');
    }

    public function approve(User $user, ProvisionalList $list): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'public_lists', 'approve');
    }

    public function publish(User $user, ProvisionalList $list): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'public_lists', 'publish');
    }

    public function delete(User $user, ProvisionalList $list): bool
    {
        return false;
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'public_lists', 'view');
    }

    public function viewBackoffice(User $user, ProvisionalList $list): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsProvisionalList($user, $list);
    }

    public function generateAnyBackoffice(User $user): bool
    {
        return $this->canGenerateBackoffice($user);
    }

    public function generateBackoffice(User $user): bool
    {
        return $this->canGenerateBackoffice($user);
    }

    public function reviewBackoffice(User $user, ProvisionalList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'review');
    }

    public function approveBackoffice(User $user, ProvisionalList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'approve');
    }

    public function publishBackoffice(User $user, ProvisionalList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'publish');
    }

    public function openComplaintPeriodBackoffice(User $user, ProvisionalList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'open_complaint_period');
    }

    public function closeComplaintPeriodBackoffice(User $user, ProvisionalList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'close_complaint_period');
    }

    public function cancelBackoffice(User $user, ProvisionalList $list): bool
    {
        return $this->canMutateBackoffice($user, $list, 'cancel');
    }

    public function archiveBackoffice(User $user, ProvisionalList $list): bool
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
        ProvisionalList $list,
        string $action,
    ): bool {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'public_lists', $action)
            && $this->municipalScope->ownsProvisionalList($user, $list);
    }

    /** @param  list<ProvisionalListStatus>  $statuses */
    private function statusIsIn(ProvisionalList $list, array $statuses): bool
    {
        $status = $this->status($list);

        return $status !== null && in_array($status, $statuses, true);
    }

    private function status(ProvisionalList $list): ?ProvisionalListStatus
    {
        $status = $list->getAttribute('status');

        if ($status instanceof ProvisionalListStatus) {
            return $status;
        }

        return is_string($status) ? ProvisionalListStatus::tryFrom($status) : null;
    }
}
