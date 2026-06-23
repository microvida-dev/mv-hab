<?php

namespace App\Models;

use App\Enums\ScoreCriterionResult;
use Database\Factories\ApplicationScoreDetailFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationScoreDetail extends Model
{
    /** @use HasFactory<ApplicationScoreDetailFactory> */
    use HasFactory;

    protected $guarded = ['id', 'points_awarded', 'manual_points', 'reviewed_by', 'reviewed_at', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'result' => ScoreCriterionResult::class,
            'points_awarded' => 'decimal:2',
            'max_points' => 'decimal:2',
            'weight' => 'decimal:3',
            'raw_value' => 'array',
            'normalized_value' => 'array',
            'requires_manual_review' => 'boolean',
            'manual_points' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ApplicationScore, $this>
     */
    public function applicationScore(): BelongsTo
    {
        return $this->belongsTo(ApplicationScore::class);
    }

    /**
     * @return BelongsTo<ScoringCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(ScoringCriterion::class, 'scoring_criterion_id');
    }

    /**
     * @return BelongsTo<ScoringRule, $this>
     */
    public function scoringRule(): BelongsTo
    {
        return $this->belongsTo(ScoringRule::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
