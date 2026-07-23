<?php

namespace App\Services\Platform;

use App\Enums\PlatformOperatorScope;
use App\Enums\PlatformOperatorStatus;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class PlatformOperatorScopeService
{
    public function hasGlobalScope(User $user): bool
    {
        return $this->scopeFor($user) === PlatformOperatorScope::Global;
    }

    public function scopeFor(User $user): ?PlatformOperatorScope
    {
        if (! $this->accountCanHoldScope($user)) {
            return null;
        }

        return PlatformOperatorAssignment::query()
            ->where('user_id', $user->getKey())
            ->where('status', PlatformOperatorStatus::Active)
            ->whereNull('revoked_at')
            ->exists()
                ? PlatformOperatorScope::Global
                : null;
    }

    public function activeAssignment(User $user): ?PlatformOperatorAssignment
    {
        if (! $this->accountCanHoldScope($user)) {
            return null;
        }

        return PlatformOperatorAssignment::query()
            ->active()
            ->where('user_id', $user->getKey())
            ->first();
    }

    public function activeCount(): int
    {
        return PlatformOperatorAssignment::query()
            ->active()
            ->whereHas('user', fn (Builder $query): Builder => $query
                ->where('status', 'active')
                ->whereNull('municipality_id'))
            ->whereDoesntHave('user.roles', fn (Builder $query): Builder => $query
                ->where('roles.name', 'candidate')
                ->where('roles.is_active', true))
            ->count();
    }

    public function isLastActive(PlatformOperatorAssignment $assignment): bool
    {
        if (! $assignment->isActive()) {
            return false;
        }

        $assignment->loadMissing('user');
        $user = $assignment->user;

        return $user instanceof User
            && $this->hasGlobalScope($user)
            && $this->activeCount() <= 1;
    }

    private function accountCanHoldScope(User $user): bool
    {
        return ($user->status ?? 'active') === 'active'
            && $user->municipality_id === null
            && ! $user->hasRole('candidate');
    }
}
