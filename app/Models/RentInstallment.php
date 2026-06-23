<?php

namespace App\Models;

use App\Enums\RentInstallmentStatus;
use Database\Factories\RentInstallmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentInstallment extends Model
{
    /** @use HasFactory<RentInstallmentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'reference', 'status', 'amount_paid', 'amount_outstanding', 'paid_at', 'overdue_at', 'cancelled_at', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RentInstallmentStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'original_amount' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_outstanding' => 'decimal:2',
            'amount_waived' => 'decimal:2',
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
            'overdue_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<TenantFinancialAccount, $this> */
    public function tenantFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(TenantFinancialAccount::class);
    }

    /** @return BelongsTo<RentSchedule, $this> */
    public function rentSchedule(): BelongsTo
    {
        return $this->belongsTo(RentSchedule::class);
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

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** @return HasOne<Arrear, $this> */
    public function arrear(): HasOne
    {
        return $this->hasOne(Arrear::class);
    }
}
