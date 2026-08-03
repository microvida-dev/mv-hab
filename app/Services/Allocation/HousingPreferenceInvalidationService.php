<?php

namespace App\Services\Allocation;

use App\Enums\ApplicationStatus;
use App\Enums\HousingCompatibilityStatus;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\Household;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Builder;

class HousingPreferenceInvalidationService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function forHousehold(Household $household, string $reason): int
    {
        return $this->invalidate(
            Application::query()
                ->where('household_id', $household->id)
                ->where('status', ApplicationStatus::Draft->value),
            $reason,
        );
    }

    public function forRegistration(
        AdhesionRegistration $registration,
        string $reason,
    ): int {
        return $this->invalidate(
            Application::query()
                ->where('adhesion_registration_id', $registration->id)
                ->where('status', ApplicationStatus::Draft->value),
            $reason,
        );
    }

    /**
     * @param  Builder<Application>  $applications
     */
    private function invalidate(Builder $applications, string $reason): int
    {
        $affected = 0;

        $applications
            ->whereHas('housingPreferences', fn ($query) => $query->whereNull('locked_at'))
            ->with('housingPreferences')
            ->each(function (Application $application) use ($reason, &$affected): void {
                $count = $application->housingPreferences()
                    ->whereNull('locked_at')
                    ->where(function ($query) use ($reason): void {
                        $query
                            ->whereNull('invalidated_at')
                            ->orWhere(
                                'compatibility_status',
                                '!=',
                                HousingCompatibilityStatus::RequiresRevalidation->value,
                            )
                            ->orWhere('invalidation_reason', '!=', $reason);
                    })
                    ->update([
                        'compatibility_status' => HousingCompatibilityStatus::RequiresRevalidation->value,
                        'invalidated_at' => now(),
                        'invalidation_reason' => $reason,
                        'updated_at' => now(),
                    ]);

                if ($count === 0) {
                    return;
                }

                $affected += $count;
                $this->auditLogger->record(
                    AuditEvents::UPDATE,
                    $application,
                    'allocations',
                    'housing_preferences_invalidated',
                    'Preferências habitacionais marcadas para revalidação.',
                    metadata: [
                        'application_id' => $application->id,
                        'preferences_count' => $count,
                        'reason' => $reason,
                    ],
                );
            });

        return $affected;
    }
}
