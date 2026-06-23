<?php

namespace App\Models;

use App\Enums\ScoringCalculationType;
use App\Enums\ScoringOperator;
use Database\Factories\ScoringCriterionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ScoringCalculationType $calculation_type
 * @property ScoringOperator|null $operator
 */
class ScoringCriterion extends Model
{
    /** @use HasFactory<ScoringCriterionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'target',
        'calculation_type',
        'operator',
        'expected_value',
        'minimum_value',
        'maximum_value',
        'points',
        'max_points',
        'weight',
        'requires_manual_review',
        'is_exclusionary',
        'is_active',
        'sort_order',
        'success_message',
        'failure_message',
        'review_message',
    ];

    protected function casts(): array
    {
        return [
            'calculation_type' => ScoringCalculationType::class,
            'operator' => ScoringOperator::class,
            'expected_value' => 'array',
            'minimum_value' => 'decimal:2',
            'maximum_value' => 'decimal:2',
            'points' => 'decimal:2',
            'max_points' => 'decimal:2',
            'weight' => 'decimal:3',
            'requires_manual_review' => 'boolean',
            'is_exclusionary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ScoringRuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(ScoringRuleSet::class, 'scoring_rule_set_id');
    }

    /**
     * @return HasMany<ScoringRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(ScoringRule::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ApplicationScoreDetail, $this>
     */
    public function scoreDetails(): HasMany
    {
        return $this->hasMany(ApplicationScoreDetail::class);
    }
}
