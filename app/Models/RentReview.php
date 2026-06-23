<?php

namespace App\Models;

use App\Enums\RentReviewStatus;
use App\Enums\RentReviewType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentReview extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'status', 'requested_at', 'calculated_at', 'approved_at', 'applied_at', 'rejected_at', 'cancelled_at', 'reviewed_by', 'approved_by', 'applied_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RentReviewStatus::class,
            'review_type' => RentReviewType::class,
            'current_rent' => 'decimal:2',
            'proposed_rent' => 'decimal:2',
            'approved_rent' => 'decimal:2',
            'effective_from' => 'date',
            'requested_at' => 'datetime',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'applied_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'income_snapshot' => 'array',
            'calculation_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<TenantFinancialAccount, $this> */
    public function tenantFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(TenantFinancialAccount::class);
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /** @return BelongsTo<User, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<RentSchedule, $this> */
    public function newRentSchedule(): BelongsTo
    {
        return $this->belongsTo(RentSchedule::class, 'new_rent_schedule_id');
    }

    /** @return HasMany<IncomeChangeDeclaration, $this> */
    public function incomeChangeDeclarations(): HasMany
    {
        return $this->hasMany(IncomeChangeDeclaration::class);
    }
}
