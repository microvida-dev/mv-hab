<?php

namespace App\Policies;

use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;

class SupportTicketAttachmentPolicy
{
    use ChecksPermissions;

    public function view(User $user, SupportTicketAttachment $attachment): bool
    {
        $ticket = $attachment->ticket;
        $message = $attachment->message;

        if ($ticket === null) {
            return false;
        }

        if ($user->hasRole('candidate')) {
            return $ticket->belongsToUser($user)
                && ($message === null || $message->isCandidateVisible())
                && $this->canAccess($user, 'support', 'view');
        }

        return $this->canAccess($user, 'support', 'view');
    }

    public function create(User $user): bool
    {
        return $this->canAccess($user, 'support', 'create') || $this->canAccess($user, 'support', 'update');
    }
}
