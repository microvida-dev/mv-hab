<?php

namespace App\Models;

use App\Enums\EligibilityRuleSetStatus;
use Carbon\CarbonInterface;
use Database\Factories\EligibilityRuleSetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $regulatory_profile_id
 */
class EligibilityRuleSet extends Model
{
    /** @use HasFactory<EligibilityRuleSetFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'program_id',
        'contest_id',
        'regulatory_profile_id',
        'name',
        'description',
        'is_default',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EligibilityRuleSetStatus::class,
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
     * @return BelongsTo<AffordableRentRegulatoryProfile, $this>
     */
    public function regulatoryProfile(): BelongsTo
    {
        return $this->belongsTo(AffordableRentRegulatoryProfile::class);
    }

    /**
     * @return HasMany<EligibilityCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(EligibilityCriterion::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<EligibilityCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(EligibilityCheck::class);
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
     * @param  Builder<EligibilityRuleSet>  $query
     * @return Builder<EligibilityRuleSet>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->activeAt(now());
    }

    /**
     * @param  Builder<EligibilityRuleSet>  $query
     * @return Builder<EligibilityRuleSet>
     */
    public function scopeActiveAt(Builder $query, CarbonInterface $referenceDate): Builder
    {
        return $query
            ->where('status', EligibilityRuleSetStatus::Active->value)
            ->where(fn (Builder $builder) => $builder
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', $referenceDate))
            ->where(fn (Builder $builder) => $builder
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', $referenceDate));
    }
}
