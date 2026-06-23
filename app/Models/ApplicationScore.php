<?php

namespace App\Models;

use App\Enums\ApplicationScoreStatus;
use Database\Factories\ApplicationScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property ApplicationScoreStatus $status
 * @property Carbon|null $locked_at
 * @property string $total_score
 * @property list<array{code?: string|null, value?: bool|float|int|string|null, direction?: string|null}>|null $tie_breaker_values
 * @property bool $excluded_from_ranking
 * @property bool $is_tied
 * @property bool $requires_manual_review
 */
class ApplicationScore extends Model
{
    /** @use HasFactory<ApplicationScoreFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'total_score',
        'automatic_score',
        'manual_score',
        'tie_breaker_values',
        'rank_position',
        'is_tied',
        'requires_manual_review',
        'excluded_from_ranking',
        'exclusion_reason',
        'calculated_at',
        'calculated_by',
        'locked_at',
        'locked_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationScoreStatus::class,
            'total_score' => 'decimal:2',
            'automatic_score' => 'decimal:2',
            'manual_score' => 'decimal:2',
            'tie_breaker_values' => 'array',
            'is_tied' => 'boolean',
            'requires_manual_review' => 'boolean',
            'excluded_from_ranking' => 'boolean',
            'calculated_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ScoringRun, $this>
     */
    public function scoringRun(): BelongsTo
    {
        return $this->belongsTo(ScoringRun::class);
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<ScoringRuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(ScoringRuleSet::class, 'scoring_rule_set_id');
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * @return HasMany<ApplicationScoreDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(ApplicationScoreDetail::class);
    }

    /**
     * @return HasOne<RankingEntry, $this>
     */
    public function rankingEntry(): HasOne
    {
        return $this->hasOne(RankingEntry::class);
    }

    public function isLocked(): bool
    {
        return $this->status === ApplicationScoreStatus::Locked || $this->locked_at !== null;
    }
}
