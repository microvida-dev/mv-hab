<?php

namespace App\Services\Documents;

use App\Enums\RequiredDocumentConditionOperator;
use App\Models\CurrentHousingSituation;
use App\Models\HouseholdMember;
use App\Models\IncomeRecord;
use App\Models\RequiredDocument;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;

class RequiredDocumentEvaluator
{
    public function applies(RequiredDocument $requiredDocument, Model $target): bool
    {
        $operator = $requiredDocument->condition_operator;

        if ($operator === RequiredDocumentConditionOperator::Always) {
            return true;
        }

        $value = $this->valueFor($requiredDocument->condition_key, $target);
        $expected = $requiredDocument->condition_value;

        return match ($operator) {
            RequiredDocumentConditionOperator::Equals => (string) $value === (string) $expected,
            RequiredDocumentConditionOperator::NotEquals => (string) $value !== (string) $expected,
            RequiredDocumentConditionOperator::GreaterThan => is_numeric($value) && is_numeric($expected) && (float) $value > (float) $expected,
            RequiredDocumentConditionOperator::LessThan => is_numeric($value) && is_numeric($expected) && (float) $value < (float) $expected,
            RequiredDocumentConditionOperator::IsTrue => $value === true || $value === 1 || $value === '1',
            RequiredDocumentConditionOperator::IsFalse => $value === false || $value === 0 || $value === '0',
            RequiredDocumentConditionOperator::Exists => filled($value),
        };
    }

    private function valueFor(string $key, Model $target): mixed
    {
        return match ($key) {
            'always' => true,
            'household_member.is_adult' => $target instanceof HouseholdMember ? (($target->age() ?? 0) >= 18) : null,
            'household_member.is_disabled' => $target instanceof HouseholdMember
                ? $target->is_disabled || $target->has_multiple_disabilities
                : null,
            'household_member.is_pregnant' => $target instanceof HouseholdMember ? $target->is_pregnant : null,
            'household_member.is_exempt_from_irs' => $target instanceof HouseholdMember ? $target->is_exempt_from_irs : null,
            'household_member.is_student' => $target instanceof HouseholdMember ? $target->is_student : null,
            'household_member.professional_status' => $target instanceof HouseholdMember ? $this->normalize($target->professional_status) : null,
            'income_record.income_source' => $target instanceof IncomeRecord ? $target->incomeSource?->getAttribute('code') : null,
            'current_housing_situation.housing_status' => $target instanceof CurrentHousingSituation ? $this->normalize($target->housing_status) : null,
            'current_housing_situation.is_at_risk_of_eviction' => $target instanceof CurrentHousingSituation ? $target->is_at_risk_of_eviction : null,
            'current_housing_situation.current_monthly_rent' => $target instanceof CurrentHousingSituation ? $target->current_monthly_rent : null,
            default => $this->normalize(data_get($target, str($key)->after('.')->toString())),
        };
    }

    private function normalize(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}
