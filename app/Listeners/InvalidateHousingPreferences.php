<?php

namespace App\Listeners;

use App\Events\HousingPreferenceInputsChanged;
use App\Models\Household;
use App\Services\Allocation\HousingPreferenceInvalidationService;

final class InvalidateHousingPreferences
{
    public function __construct(
        private readonly HousingPreferenceInvalidationService $invalidation,
    ) {}

    public function handle(HousingPreferenceInputsChanged $event): void
    {
        if ($event->subject instanceof Household) {
            $this->invalidation->forHousehold(
                $event->subject,
                $event->reason,
            );

            return;
        }

        $this->invalidation->forRegistration(
            $event->subject,
            $event->reason,
        );
    }
}
