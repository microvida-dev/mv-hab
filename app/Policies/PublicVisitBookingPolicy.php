<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PublicVisitBooking;
use App\Models\User;
use App\Services\Visits\PublicVisitBookingMunicipalScopeService;

class PublicVisitBookingPolicy
{
    public function __construct(
        private readonly PublicVisitBookingMunicipalScopeService $scope,
    ) {}

    public function viewAnyBackoffice(User $user): bool
    {
        return $user->hasPermission('visits.view')
            && $this->scope->hasScope($user);
    }

    public function viewBackoffice(
        User $user,
        PublicVisitBooking $booking,
    ): bool {
        return $this->viewAnyBackoffice($user)
            && $this->scope->owns($user, $booking);
    }

    public function cancelBackoffice(
        User $user,
        PublicVisitBooking $booking,
    ): bool {
        return $user->hasPermission('visits.cancel')
            && $booking->isActive()
            && $this->scope->owns($user, $booking);
    }

    public function completeBackoffice(
        User $user,
        PublicVisitBooking $booking,
    ): bool {
        return $user->hasPermission('visits.complete')
            && $booking->isActive()
            && $this->scope->owns($user, $booking);
    }

    public function markNoShowBackoffice(
        User $user,
        PublicVisitBooking $booking,
    ): bool {
        return $user->hasPermission('visits.mark_no_show')
            && $booking->isActive()
            && $this->scope->owns($user, $booking);
    }
}
