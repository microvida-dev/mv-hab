<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\CandidateDeclaredData;
use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\CurrentHousingSituation;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CandidateDeclaredDataResolver
{
    public function resolve(Application $application): CandidateDeclaredData
    {
        $application->loadMissing([
            'user',
            'adhesionRegistration',
            'household.members.incomeRecords',
            'household.incomeRecords',
            'currentHousingSituation',
        ]);

        $registrationValue = $application->getRelationValue('adhesionRegistration');
        $householdValue = $application->getRelationValue('household');
        $housingValue = $application->getRelationValue('currentHousingSituation');
        $userValue = $application->getRelationValue('user');

        $registration = $registrationValue instanceof AdhesionRegistration
            ? $registrationValue
            : null;
        $household = $householdValue instanceof Household
            ? $householdValue
            : null;
        $membersValue = $household?->getRelationValue('members');
        $members = $membersValue instanceof Collection
            ? $membersValue->filter(fn ($member): bool => $member instanceof HouseholdMember)->values()
            : collect();
        $incomeRecords = $this->incomeRecords($household, $members);
        $housing = $housingValue instanceof CurrentHousingSituation
            ? $housingValue
            : null;
        $user = $userValue instanceof User ? $userValue : null;

        return new CandidateDeclaredData(
            applicationId: (int) $application->id,
            identity: [
                'name' => $this->identityName($registration, $user),
                'nif' => $registration?->nif,
                'birth_date' => $this->dateValue($registration?->birth_date),
                'document_number' => $registration?->document_number,
                'nationality' => $registration?->nationality,
            ],
            household: [
                'members_count' => $this->membersCount($household, $members),
                'dependents_count' => $members->filter(fn (HouseholdMember $member): bool => (bool) $member->is_dependent)->count(),
                'disabled_members_count' => $members->filter(fn (HouseholdMember $member): bool => (bool) $member->is_disabled)->count(),
                'max_disability_percentage' => $this->maxDisabilityPercentage($members),
            ],
            income: [
                'monthly_total' => $this->sumMoney($incomeRecords, 'monthly_amount'),
                'annual_total' => $this->sumMoney($incomeRecords, 'annual_amount'),
                'records_count' => $incomeRecords->count(),
            ],
            housing: [
                'address' => $this->housingAddress($housing),
                'rent_amount' => $housing instanceof CurrentHousingSituation ? $this->money($housing->current_monthly_rent) : null,
                'city' => $housing instanceof CurrentHousingSituation ? $housing->current_city : null,
                'municipality' => $housing instanceof CurrentHousingSituation ? $housing->current_municipality : null,
            ],
        );
    }

    /**
     * @param  Collection<int, HouseholdMember>  $members
     * @return Collection<int, IncomeRecord>
     */
    private function incomeRecords(?Household $household, Collection $members): Collection
    {
        $records = $household instanceof Household ? $household->incomeRecords : collect();

        if ($records->isNotEmpty()) {
            return $records->values();
        }

        return $members
            ->flatMap(fn (HouseholdMember $member): Collection => $member->incomeRecords)
            ->values();
    }

    /**
     * @param  Collection<int, HouseholdMember>  $members
     */
    private function membersCount(?Household $household, Collection $members): int
    {
        if ($members->isNotEmpty()) {
            return $members->count();
        }

        return $household instanceof Household ? (int) $household->members_count : 0;
    }

    /**
     * @param  Collection<int, HouseholdMember>  $members
     */
    private function maxDisabilityPercentage(Collection $members): ?float
    {
        $percentages = $members
            ->map(fn (HouseholdMember $member): ?float => $this->money($member->disability_percentage))
            ->filter(fn (?float $value): bool => $value !== null);

        return $percentages->isEmpty() ? null : (float) $percentages->max();
    }

    /**
     * @param  Collection<int, IncomeRecord>  $records
     */
    private function sumMoney(Collection $records, string $column): float
    {
        return (float) $records
            ->map(fn (IncomeRecord $record): float => $this->money($record->{$column}) ?? 0.0)
            ->sum();
    }

    private function housingAddress(?CurrentHousingSituation $housing): ?string
    {
        if (! $housing instanceof CurrentHousingSituation) {
            return null;
        }

        $parts = array_filter([
            $housing->current_address,
            $housing->current_postal_code,
            $housing->current_city,
            $housing->current_municipality,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function identityName(?AdhesionRegistration $registration, ?User $user): ?string
    {
        $name = data_get($registration, 'full_name') ?: data_get($user, 'name');

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
