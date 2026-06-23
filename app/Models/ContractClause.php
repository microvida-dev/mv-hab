<?php

namespace App\Models;

use App\Enums\ContractClauseStatus;
use Database\Factories\ContractClauseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ContractClauseStatus $status
 */
class ContractClause extends Model
{
    /** @use HasFactory<ContractClauseFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ContractClauseStatus::class,
            'is_mandatory' => 'boolean',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<ContractTemplateClause, $this> */
    public function templateClauses(): HasMany
    {
        return $this->hasMany(ContractTemplateClause::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractClauseStatus::Active->value)
            ->where(fn (Builder $builder) => $builder->whereNull('effective_from')->orWhere('effective_from', '<=', today()))
            ->where(fn (Builder $builder) => $builder->whereNull('effective_until')->orWhere('effective_until', '>=', today()));
    }
}
