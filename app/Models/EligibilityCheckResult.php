<?php

namespace App\Models;

use App\Enums\EligibilityCriterionCategory;
use App\Enums\EligibilityCriterionResult;
use App\Enums\EligibilityOperator;
use Database\Factories\EligibilityCheckResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityCheckResult extends Model
{
    /** @use HasFactory<EligibilityCheckResultFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'category' => EligibilityCriterionCategory::class,
            'result' => EligibilityCriterionResult::class,
            'operator' => EligibilityOperator::class,
            'actual_value' => 'array',
            'expected_value' => 'array',
            'requires_manual_review' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<EligibilityCheck, $this>
     */
    public function check(): BelongsTo
    {
        return $this->belongsTo(EligibilityCheck::class, 'eligibility_check_id');
    }

    /**
     * @return BelongsTo<EligibilityCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(EligibilityCriterion::class, 'eligibility_criterion_id');
    }
}
