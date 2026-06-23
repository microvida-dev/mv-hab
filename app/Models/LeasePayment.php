<?php

namespace App\Models;

use App\Enums\LeasePaymentStatus;
use Database\Factories\LeasePaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeasePayment extends Model
{
    /** @use HasFactory<LeasePaymentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'payment_number', 'status', 'allocated_amount', 'unallocated_amount', 'confirmed_at', 'reversed_at', 'confirmed_by', 'reversed_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => LeasePaymentStatus::class,
            'amount' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
            'unallocated_amount' => 'decimal:2',
            'payment_date' => 'date',
            'value_date' => 'date',
            'received_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'reversed_at' => 'datetime',
            'metadata' => 'array',
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

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** @return HasOne<PaymentReceipt, $this> */
    public function receipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class);
    }
}
