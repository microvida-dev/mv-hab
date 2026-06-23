<?php

namespace App\Models;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRuleSetStatus;
use Database\Factories\AllocationRuleSetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AllocationRuleSet extends Model
{
    /** @use HasFactory<AllocationRuleSetFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => AllocationRuleSetStatus::class,
            'allocation_method' => AllocationMethod::class,
            'allow_preferences' => 'boolean',
            'allow_lottery' => 'boolean',
            'allow_manual_override' => 'boolean',
            'requires_acceptance' => 'boolean',
            'auto_call_next_on_refusal' => 'boolean',
            'auto_call_next_on_expiry' => 'boolean',
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
     * @return HasMany<AllocationRun, $this>
     */
    public function allocationRuns(): HasMany
    {
        return $this->hasMany(AllocationRun::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AllocationRuleSetStatus::Active->value);
    }
}
