<?php

namespace App\Http\Requests\Simulator;

use App\Http\Requests\Simulator\Concerns\ValidatesSimulationInput;
use App\Models\AdhesionRegistration;
use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateSimulationRequest extends FormRequest
{
    use ValidatesSimulationInput;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('candidate') === true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->boolean('use_registration_data')) {
            return;
        }

        $user = $this->user();

        if ($user === null) {
            return;
        }

        $registration = AdhesionRegistration::query()
            ->where('user_id', $user->id)
            ->with(['household.members', 'currentHousingSituation'])
            ->latest()
            ->first();

        if (! $registration instanceof AdhesionRegistration) {
            return;
        }

        $householdRelation = $registration->getRelations()['household'] ?? null;
        $household = $householdRelation instanceof Household ? $householdRelation : null;
        $housingSituation = $registration->currentHousingSituation;

        if ($household instanceof Household) {
            $members = $household->members;
            $monthlyIncome = $household->monthly_income;
        } else {
            $members = collect();
            $monthlyIncome = 0;
        }

        $this->merge([
            'housing_status' => $housingSituation?->housing_status?->value,
            'household_members_count' => $members->count() ?: 1,
            'adults_count' => $members->filter(fn ($member) => ($member->age() ?? 0) >= 18)->count() ?: 1,
            'dependents_count' => $members->where('is_dependent', true)->count(),
            'monthly_income' => $monthlyIncome,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->simulationRules();
    }
}
