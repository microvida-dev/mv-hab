<?php

namespace App\Services\Allocation;

use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\HousingPreference;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HousingPreferenceService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * @return Collection<int, ContestHousingUnit>
     */
    public function availableFor(Application $application): Collection
    {
        return ContestHousingUnit::query()
            ->available()
            ->where('contest_id', $application->contest_id)
            ->with('housingUnit')
            ->orderBy('typology')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<array<string, mixed>>  $preferences
     */
    public function replace(Application $application, array $preferences, User $candidate, bool $submit = false): void
    {
        if ($application->user_id !== $candidate->id) {
            throw ValidationException::withMessages(['application' => 'Só pode alterar preferências da sua candidatura.']);
        }

        if ($application->allocations()->exists()) {
            throw ValidationException::withMessages(['preferences' => 'As preferências ficam bloqueadas após existir execução de atribuição.']);
        }

        $orders = collect($preferences)->pluck('preference_order');
        if ($orders->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['preferences' => 'A ordem de preferência deve ser única.']);
        }

        DB::transaction(function () use ($application, $preferences, $candidate, $submit) {
            $application->housingPreferences()->delete();

            foreach ($preferences as $preference) {
                $unit = ContestHousingUnit::query()
                    ->whereKey($preference['contest_housing_unit_id'])
                    ->where('contest_id', $application->contest_id)
                    ->available()
                    ->firstOrFail();

                $preferenceModel = new HousingPreference([
                    'preference_order' => $preference['preference_order'],
                    'notes' => $preference['notes'] ?? null,
                ]);
                $preferenceModel->forceFill([
                    'application_id' => $application->id,
                    'user_id' => $candidate->id,
                    'contest_id' => $application->contest_id,
                    'contest_housing_unit_id' => $unit->id,
                    'housing_unit_id' => $unit->housing_unit_id,
                    'submitted_at' => $submit ? now() : null,
                ])->save();
            }

            $this->auditLogger->record(AuditEvents::UPDATE, $application, 'allocations', 'housing_preferences_update', 'Preferências de habitação atualizadas.');
        });
    }
}
