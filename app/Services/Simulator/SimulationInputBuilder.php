<?php

namespace App\Services\Simulator;

use App\Models\AdhesionRegistration;
use App\Models\Household;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Arr;

class SimulationInputBuilder
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function fromRequest(array $data): array
    {
        $householdMembers = $this->integer($data, 'household_members_count');
        $dependents = $this->integer($data, 'dependents_count') ?? 0;
        $adults = $this->integer($data, 'adults_count') ?? max(($householdMembers ?? 0) - $dependents, 0);

        return [
            'contest_id' => $this->integer($data, 'contest_id'),
            'household_members_count' => $householdMembers,
            'adults_count' => $adults,
            'dependents_count' => $dependents,
            'disabled_members_count' => $this->integer($data, 'disabled_members_count') ?? 0,
            'monthly_income' => $this->float($data, 'monthly_income'),
            'annual_income' => $this->float($data, 'annual_income'),
            'current_monthly_rent' => $this->float($data, 'current_monthly_rent'),
            'housing_status' => $this->string($data, 'housing_status'),
            'preferred_parishes' => $this->list($data, 'preferred_parishes'),
            'preferred_typologies' => $this->list($data, 'preferred_typologies'),
            'has_accessibility_needs' => $this->boolean($data, 'has_accessibility_needs'),
            'has_property' => $this->boolean($data, 'has_property'),
            'receives_housing_support' => $this->boolean($data, 'receives_housing_support'),
            'has_municipal_debt' => $this->boolean($data, 'has_municipal_debt'),
            'tax_regularized' => $this->nullableBoolean($data, 'tax_regularized'),
            'social_security_regularized' => $this->nullableBoolean($data, 'social_security_regularized'),
            'has_residence_permit' => $this->nullableBoolean($data, 'has_residence_permit'),
            'false_declarations_history' => $this->boolean($data, 'false_declarations_history'),
            'previous_municipal_eviction' => $this->boolean($data, 'previous_municipal_eviction'),
            'source' => 'request',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function fromUser(User $user, array $overrides = []): array
    {
        $registration = $user->adhesionRegistration()
            ->with(['household.members', 'household.incomeRecords', 'currentHousingSituation'])
            ->first();

        if (! $registration instanceof AdhesionRegistration) {
            return array_replace($this->fromRequest($overrides), [
                'source' => 'user_without_registration',
            ]);
        }

        $household = $registration->household;
        $members = $household instanceof Household ? $household->members : collect();
        $incomeRecords = $household instanceof Household ? $household->incomeRecords : collect();
        $currentHousing = $registration->currentHousingSituation()->first();
        $dependents = $members->where('is_dependent', true)->count();
        $disabled = $members->where('is_disabled', true)->count() + $members->where('has_multiple_disabilities', true)->count();
        $monthlyIncome = (float) $incomeRecords->sum('monthly_amount');

        if ($monthlyIncome <= 0 && $household instanceof Household) {
            $monthlyIncome = (float) $household->monthly_income;
        }

        $input = [
            'contest_id' => null,
            'household_members_count' => $members->isNotEmpty() ? $members->count() : ($household?->members_count),
            'adults_count' => max($members->count() - $dependents, 0),
            'dependents_count' => $dependents,
            'disabled_members_count' => $disabled,
            'monthly_income' => $monthlyIncome > 0 ? $monthlyIncome : null,
            'annual_income' => $monthlyIncome > 0 ? round($monthlyIncome * 14, 2) : null,
            'current_monthly_rent' => $currentHousing?->current_monthly_rent,
            'housing_status' => $this->enumValue($currentHousing?->getAttribute('housing_status')),
            'preferred_parishes' => [],
            'preferred_typologies' => [],
            'has_accessibility_needs' => $disabled > 0 || (bool) $currentHousing?->has_accessibility_needs,
            'has_property' => false,
            'receives_housing_support' => false,
            'has_municipal_debt' => false,
            'tax_regularized' => null,
            'social_security_regularized' => null,
            'has_residence_permit' => null,
            'false_declarations_history' => false,
            'previous_municipal_eviction' => false,
            'registration_id' => $registration->id,
            'registration_status' => $this->enumValue($registration->getAttribute('status')),
            'source' => 'user_registration',
        ];

        return array_replace($input, array_filter($this->fromRequest($overrides), static fn (mixed $value): bool => $value !== null && $value !== []));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function integer(array $data, string $key): ?int
    {
        $value = Arr::get($data, $key);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function float(array $data, string $key): ?float
    {
        $value = Arr::get($data, $key);

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function string(array $data, string $key): ?string
    {
        $value = Arr::get($data, $key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function boolean(array $data, string $key): bool
    {
        return filter_var(Arr::get($data, $key, false), FILTER_VALIDATE_BOOL);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function nullableBoolean(array $data, string $key): ?bool
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        return filter_var($data[$key], FILTER_VALIDATE_BOOL);
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            return is_string($value->value) ? $value->value : (string) $value->value;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function list(array $data, string $key): array
    {
        $value = Arr::get($data, $key, []);
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => is_scalar($item) ? trim((string) $item) : '',
            $values,
        )));
    }
}
