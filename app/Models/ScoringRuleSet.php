<?php

namespace App\Models;

use App\Enums\ScoringRuleSetStatus;
use Database\Factories\ScoringRuleSetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ScoringRuleSetStatus $status
 */
class ScoringRuleSet extends Model
{
    /** @use HasFactory<ScoringRuleSetFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'contest_id',
        'name',
        'description',
        'status',
        'is_default',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScoringRuleSetStatus::class,
            'is_default' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
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
     * @return HasMany<ScoringCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(ScoringCriterion::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<TieBreakerRule, $this>
     */
    public function tieBreakerRules(): HasMany
    {
        return $this->hasMany(TieBreakerRule::class)->orderBy('priority_order')->orderBy('id');
    }

    /**
     * @return HasMany<ScoringRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ScoringRun::class);
    }

    /**
     * @return HasMany<ApplicationScore, $this>
     */
    public function applicationScores(): HasMany
    {
        return $this->hasMany(ApplicationScore::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @param  Builder<ScoringRuleSet>  $query
     * @return Builder<ScoringRuleSet>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', ScoringRuleSetStatus::Active->value)
            ->where(fn (Builder $builder) => $builder
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $builder) => $builder
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now()));
    }
}
