<?php

namespace App\Models;

use App\Enums\ScoringRunStatus;
use Database\Factories\ScoringRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoringRun extends Model
{
    /** @use HasFactory<ScoringRunFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'scoring_rule_set_id',
        'program_id',
        'contest_id',
        'status',
        'started_by',
        'started_at',
        'completed_at',
        'failed_at',
        'failure_reason',
        'total_applications',
        'scored_applications',
        'manual_review_applications',
        'excluded_applications',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScoringRunStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
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
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * @return HasMany<ApplicationScore, $this>
     */
    public function applicationScores(): HasMany
    {
        return $this->hasMany(ApplicationScore::class);
    }

    /**
     * @return HasMany<RankingSnapshot, $this>
     */
    public function rankingSnapshots(): HasMany
    {
        return $this->hasMany(RankingSnapshot::class);
    }
}
