<?php

namespace App\Models;

use App\Enums\TenantPaymentStatus;
use Database\Factories\TenantPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property TenantPaymentStatus $status
 * @property-read TenantInvoice|null $invoice
 */
class TenantPayment extends Model
{
    /** @use HasFactory<TenantPaymentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'payment_number', 'status', 'allocated_amount', 'unallocated_amount', 'confirmed_at', 'reconciled_at', 'failed_at', 'cancelled_at', 'registered_by', 'confirmed_by', 'reconciled_by', 'cancelled_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => TenantPaymentStatus::class,
            'amount' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
            'unallocated_amount' => 'decimal:2',
            'payment_date' => 'date',
            'value_date' => 'date',
            'registered_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<TenantInvoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TenantInvoice::class, 'tenant_invoice_id');
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

    /** @return BelongsTo<LeasePayment, $this> */
    public function sourceLeasePayment(): BelongsTo
    {
        return $this->belongsTo(LeasePayment::class, 'source_lease_payment_id');
    }
}
