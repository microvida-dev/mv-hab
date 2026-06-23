<?php

namespace App\Models;

use App\Enums\ContractTemplateStatus;
use Database\Factories\ContractTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ContractTemplateStatus $status
 */
class ContractTemplate extends Model
{
    /** @use HasFactory<ContractTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ContractTemplateStatus::class,
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
        return $this->hasMany(ContractTemplateClause::class)->orderBy('sort_order');
    }

    /** @return BelongsToMany<ContractClause, $this> */
    public function clauses(): BelongsToMany
    {
        return $this->belongsToMany(ContractClause::class, 'contract_template_clauses')
            ->withPivot(['sort_order', 'is_active'])
            ->withTimestamps()
            ->orderBy('contract_template_clauses.sort_order');
    }

    /** @return HasMany<Contract, $this> */
    public function leaseContracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractTemplateStatus::Active->value)
            ->where(fn (Builder $builder) => $builder->whereNull('effective_from')->orWhere('effective_from', '<=', today()))
            ->where(fn (Builder $builder) => $builder->whereNull('effective_until')->orWhere('effective_until', '>=', today()));
    }
}
