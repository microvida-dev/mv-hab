<?php

namespace App\Policies;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\Municipalities\MunicipalRecordScopeService;

class ComplaintPolicy
{
    use ChecksPermissions;

    public function __construct(private readonly MunicipalRecordScopeService $municipalScope) {}

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'complaints', 'view');
    }

    public function view(User $user, Complaint $complaint): bool
    {
        return $user->hasRole('candidate')
            ? $complaint->user_id === $user->id && $complaint->candidate_visible && $this->canAccess($user, 'complaints', 'view')
            : $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') && $this->canAccess($user, 'complaints', 'create');
    }

    public function update(User $user, Complaint $complaint): bool
    {
        if ($user->hasRole('candidate')) {
            return $complaint->user_id === $user->id && $this->status($complaint) === ComplaintStatus::Draft && $this->canAccess($user, 'complaints', 'update');
        }

        return ! $user->hasRole('auditor') && $this->canAccess($user, 'complaints', 'update');
    }

    public function submit(User $user, Complaint $complaint): bool
    {
        return $this->update($user, $complaint);
    }

    public function approve(User $user, Complaint $complaint): bool
    {
        return ! $user->hasRole(['candidate', 'auditor']) && $this->canAccess($user, 'complaints', 'approve');
    }

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $user->municipality_id !== null
            && $this->canAccess($user, 'complaints', 'view');
    }

    public function viewBackoffice(User $user, Complaint $complaint): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsComplaint($user, $complaint);
    }

    public function createBackoffice(User $user, Complaint $complaint): bool
    {
        return $this->canMutateBackoffice($user, $complaint, 'review');
    }

    public function assignBackoffice(User $user, Complaint $complaint): bool
    {
        return $this->canMutateBackoffice($user, $complaint, 'assign');
    }

    public function markReceivedBackoffice(User $user, Complaint $complaint): bool
    {
        return $this->canMutateBackoffice($user, $complaint, 'mark_received');
    }

    public function reviewBackoffice(User $user, Complaint $complaint): bool
    {
        return $this->canMutateBackoffice($user, $complaint, 'review');
    }

    public function closeBackoffice(User $user, Complaint $complaint): bool
    {
        return $this->canMutateBackoffice($user, $complaint, 'close');
    }

    public function requestInformationBackoffice(User $user, Complaint $complaint): bool
    {
        return $this->canMutateBackoffice($user, $complaint, 'request_information');
    }

    private function canMutateBackoffice(User $user, Complaint $complaint, string $action): bool
    {
        return ! $user->hasRole(['candidate', 'auditor'])
            && $this->canAccess($user, 'complaints', $action)
            && $this->municipalScope->ownsComplaint($user, $complaint);
    }

    private function status(Complaint $complaint): ?ComplaintStatus
    {
        $status = $complaint->getAttribute('status');

        if ($status instanceof ComplaintStatus) {
            return $status;
        }

        return is_string($status) ? ComplaintStatus::tryFrom($status) : null;
    }
}
