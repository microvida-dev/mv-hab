<?php

namespace App\Services\Allocation;

use App\Enums\TypologyAdequacyResult;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\TypologyAdequacyRule;
use Illuminate\Database\Eloquent\Collection;

class TypologyAdequacyService
{
    public function evaluate(Application $application, ContestHousingUnit $unit): TypologyAdequacyResult
    {
        $rules = $this->rulesFor($unit);

        if ($rules->isEmpty()) {
            return TypologyAdequacyResult::RequiresManualReview;
        }

        $composition = $this->composition($application);

        if ($composition['members'] === 0) {
            return TypologyAdequacyResult::RequiresManualReview;
        }

        foreach ($rules as $rule) {
            if ($this->matches($rule, $unit, $composition)) {
                return TypologyAdequacyResult::Adequate;
            }
        }

        return TypologyAdequacyResult::Inadequate;
    }

    /**
     * @return Collection<int, TypologyAdequacyRule>
     */
    public function rulesFor(ContestHousingUnit $unit): Collection
    {
        $contestRules = TypologyAdequacyRule::query()
            ->active()
            ->where('contest_id', $unit->contest_id)
            ->orderBy('priority_order')
            ->get();

        if ($contestRules->isNotEmpty()) {
            return $contestRules;
        }

        return TypologyAdequacyRule::query()
            ->active()
            ->where('program_id', $unit->program_id)
            ->whereNull('contest_id')
            ->orderBy('priority_order')
            ->get();
    }

    /**
     * @return array<string|int, mixed>
     */
    public function composition(Application $application): array
    {
        $members = $application->household?->members()->get() ?? collect();

        return [
            'members' => $members->count(),
            'adults' => $members->filter(fn ($member) => ($member->age() ?? 18) >= 18)->count(),
            'children' => $members->filter(fn ($member) => ($member->age() ?? 18) < 18)->count(),
            'requires_accessibility' => $members->contains(fn ($member) => (bool) $member->has_reduced_mobility || (bool) $member->is_disabled),
        ];
    }

    /**
     * @param  array<int|string, mixed>  $composition
     */
    private function matches(TypologyAdequacyRule $rule, ContestHousingUnit $unit, array $composition): bool
    {
        foreach ([
            'min_household_members' => ['members', '>='],
            'max_household_members' => ['members', '<='],
            'min_adults' => ['adults', '>='],
            'max_adults' => ['adults', '<='],
            'min_children' => ['children', '>='],
            'max_children' => ['children', '<='],
        ] as $field => [$metric, $operator]) {
            if ($rule->{$field} !== null && ! $this->compare($composition[$metric], $operator, (int) $rule->{$field})) {
                return false;
            }
        }

        if ($rule->min_bedrooms !== null && ($unit->bedrooms ?? 0) < $rule->min_bedrooms) {
            return false;
        }

        if ($rule->max_bedrooms !== null && ($unit->bedrooms ?? 0) > $rule->max_bedrooms) {
            return false;
        }

        if ($rule->typology && $unit->typology !== $rule->typology) {
            return false;
        }

        if ($rule->requires_accessibility && (! $unit->accessible || ! $composition['requires_accessibility'])) {
            return false;
        }

        return true;
    }

    private function compare(int $actual, string $operator, int $expected): bool
    {
        return $operator === '>=' ? $actual >= $expected : $actual <= $expected;
    }
}
