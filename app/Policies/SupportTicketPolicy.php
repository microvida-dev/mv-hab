<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SupportTicketPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->canAccess($user, 'support', 'view');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('candidate')
            ? $ticket->belongsToUser($user) && $this->canAccess($user, 'support', 'view')
            : $this->canAccess($user, 'support', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') && $this->canAccess($user, 'support', 'create');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('candidate')
            ? $ticket->belongsToUser($user) && $ticket->acceptsCandidateReply() && $this->canAccess($user, 'support', 'update')
            : $this->canAccess($user, 'support', 'update');
    }

    public function assign(User $user, SupportTicket $ticket): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'support', 'update');
    }

    public function resolve(User $user, SupportTicket $ticket): bool
    {
        return ! $user->hasRole('candidate') && $this->canAccess($user, 'support', 'approve');
    }
}
