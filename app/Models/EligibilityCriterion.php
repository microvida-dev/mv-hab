<?php

namespace App\Models;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityOperator;
use Database\Factories\EligibilityCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $code
 * @property string $name
 * @property EligibilityCriterionCategory $category
 * @property EligibilityOperator $operator
 * @property array<string, bool|float|int|string|null>|null $expected_value
 */
class EligibilityCriterion extends Model
{
    /** @use HasFactory<EligibilityCriterionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'target',
        'operator',
        'expected_value',
        'minimum_value',
        'maximum_value',
        'unit',
        'is_mandatory',
        'requires_manual_review',
        'failure_message',
        'success_message',
        'review_message',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => EligibilityCriterionCategory::class,
            'operator' => EligibilityOperator::class,
            'expected_value' => 'array',
            'minimum_value' => 'decimal:2',
            'maximum_value' => 'decimal:2',
            'is_mandatory' => 'boolean',
            'requires_manual_review' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<EligibilityRuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(EligibilityRuleSet::class, 'eligibility_rule_set_id');
    }

    /**
     * @return HasMany<EligibilityCheckResult, $this>
     */
    public function checkResults(): HasMany
    {
        return $this->hasMany(EligibilityCheckResult::class);
    }
}
