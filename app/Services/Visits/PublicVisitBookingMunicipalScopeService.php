<?php

declare(strict_types=1);

namespace App\Services\Visits;

use App\Models\PublicVisitBooking;
use App\Models\User;
use App\Services\Platform\PlatformOperatorScopeService;
use Illuminate\Database\Eloquent\Builder;

final class PublicVisitBookingMunicipalScopeService
{
    public function __construct(
        private readonly PlatformOperatorScopeService $platformScope,
    ) {}

    /**
     * @param  Builder<PublicVisitBooking>  $query
     * @return Builder<PublicVisitBooking>
     */
    public function query(Builder $query, User $user): Builder
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return $query;
        }

        if ($user->municipality_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'municipality_id',
            (int) $user->municipality_id,
        );
    }

    public function owns(User $user, PublicVisitBooking $booking): bool
    {
        if ($this->platformScope->hasGlobalScope($user)) {
            return true;
        }

        return $user->municipality_id !== null
            && (int) $user->municipality_id
                === (int) $booking->municipality_id;
    }

    public function hasScope(User $user): bool
    {
        return $this->platformScope->hasGlobalScope($user)
            || $user->municipality_id !== null;
    }
}
