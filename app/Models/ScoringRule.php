<?php

namespace App\Models;

use App\Enums\ScoringOperator;
use Database\Factories\ScoringRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoringRule extends Model
{
    /** @use HasFactory<ScoringRuleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'label',
        'description',
        'operator',
        'value',
        'minimum_value',
        'maximum_value',
        'points',
        'weight',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'operator' => ScoringOperator::class,
            'value' => 'array',
            'minimum_value' => 'decimal:2',
            'maximum_value' => 'decimal:2',
            'points' => 'decimal:2',
            'weight' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ScoringCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(ScoringCriterion::class, 'scoring_criterion_id');
    }
}
