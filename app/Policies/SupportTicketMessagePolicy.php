<?php

namespace App\Policies;

use App\Enums\MessageVisibility;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SupportTicketMessagePolicy
{
    use ChecksPermissions;

    public function view(User $user, SupportTicketMessage $message): bool
    {
        $ticket = $message->ticket;

        if (! $ticket instanceof SupportTicket) {
            return false;
        }

        if ($user->hasRole('candidate')) {
            return $ticket->belongsToUser($user)
                && $message->isCandidateVisible()
                && $this->canAccess($user, 'support', 'view');
        }

        return $this->canAccess($user, 'support', 'view');
    }

    public function create(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('candidate')
            ? $ticket->belongsToUser($user) && $ticket->acceptsCandidateReply() && $this->canAccess($user, 'support', 'update')
            : $this->canAccess($user, 'support', 'update');
    }

    public function createInternal(User $user, SupportTicket $ticket): bool
    {
        return ! $user->hasRole('candidate') && $this->create($user, $ticket);
    }

    public function visibilityAllowed(User $user, MessageVisibility $visibility): bool
    {
        return ! $user->hasRole('candidate') || $visibility === MessageVisibility::CandidateVisible;
    }
}
