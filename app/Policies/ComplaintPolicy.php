<?php

namespace App\Policies;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class ComplaintPolicy
{
    use ChecksPermissions;

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

    private function status(Complaint $complaint): ?ComplaintStatus
    {
        $status = $complaint->getAttribute('status');

        if ($status instanceof ComplaintStatus) {
            return $status;
        }

        return is_string($status) ? ComplaintStatus::tryFrom($status) : null;
    }
}
