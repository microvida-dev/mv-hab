<?php

namespace App\Policies;

use App\Enums\TicketCategory;
use App\Models\SupportTicket;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use App\Services\CandidateExperience\TenantSupportEligibilityService;
use App\Services\Municipalities\MunicipalRecordScopeService;

class SupportTicketPolicy
{
    use ChecksPermissions;

    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly TenantSupportEligibilityService $tenantSupport,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->hasRole('candidate')
            ? $this->tenantSupport->isAvailableFor($user)
                && $this->canAccess($user, 'support', 'view')
            : $this->canAccess($user, 'support', 'view');
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('candidate')
            ? $this->tenantSupport->isAvailableFor($user)
                && $ticket->belongsToUser($user)
                && $this->canAccess($user, 'support', 'view')
            : $this->canAccess($user, 'support', 'view') && $this->canAccessCategory($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('candidate')
            && $this->tenantSupport->isAvailableFor($user)
            && $this->canAccess($user, 'support', 'create');
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('candidate')
            ? $this->tenantSupport->isAvailableFor($user)
                && $ticket->belongsToUser($user)
                && $ticket->acceptsCandidateReply()
                && $this->canAccess($user, 'support', 'update')
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

    public function viewAnyBackoffice(User $user): bool
    {
        return ! $user->hasRole('candidate')
            && $this->canAccess($user, 'support', 'view')
            && $this->municipalScope->hasMunicipalOrGlobalScope($user);
    }

    public function viewBackoffice(User $user, SupportTicket $ticket): bool
    {
        return $this->viewAnyBackoffice($user)
            && $this->municipalScope->ownsSupportTicket($user, $ticket)
            && $this->canAccessCategory($user, $ticket);
    }

    public function assignBackoffice(User $user, SupportTicket $ticket): bool
    {
        return $this->canAccess($user, 'support', 'assign')
            && $this->municipalScope->ownsSupportTicket($user, $ticket)
            && $this->canAccessCategory($user, $ticket);
    }

    public function resolveBackoffice(User $user, SupportTicket $ticket): bool
    {
        return $this->canAccess($user, 'support', 'resolve')
            && $this->municipalScope->ownsSupportTicket($user, $ticket)
            && $this->canAccessCategory($user, $ticket);
    }

    public function messageBackoffice(User $user, SupportTicket $ticket): bool
    {
        return $this->canAccess($user, 'support', 'message')
            && $this->municipalScope->ownsSupportTicket($user, $ticket)
            && $this->canAccessCategory($user, $ticket);
    }

    private function canAccessCategory(User $user, SupportTicket $ticket): bool
    {
        $category = TicketCategory::tryFrom((string) $ticket->getRawOriginal('category'));
        $roles = $category?->requiredBackofficeRoles() ?? [];

        return $roles === [] || $user->hasRole($roles);
    }
}
