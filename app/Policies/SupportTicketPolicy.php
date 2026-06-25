<?php

namespace App\Policies;

use App\Enums\TicketCategory;
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
            : $this->canAccess($user, 'support', 'view') && $this->canAccessCategory($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate') && $this->canAccess($user, 'support', 'create');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('candidate')
            ? $ticket->belongsToUser($user) && $ticket->acceptsCandidateReply() && $this->canAccess($user, 'support', 'update')
            : $this->canAccess($user, 'support', 'update') && $this->canAccessCategory($user, $ticket);
    }

    public function assign(User $user, SupportTicket $ticket): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'support', 'update')
            && $this->canAccessCategory($user, $ticket);
    }

    public function resolve(User $user, SupportTicket $ticket): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'support', 'approve')
            && $this->canAccessCategory($user, $ticket);
    }

    private function canAccessCategory(User $user, SupportTicket $ticket): bool
    {
        $category = TicketCategory::tryFrom((string) $ticket->getRawOriginal('category'));
        $roles = $category?->requiredBackofficeRoles() ?? [];

        return $roles === [] || $user->hasRole($roles);
    }
}
