<?php

namespace App\Models;

use App\Enums\PaymentAllocationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    protected $guarded = ['id', 'status', 'reversed_at', 'reversed_by', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'status' => PaymentAllocationStatus::class,
            'amount' => 'decimal:2',
            'allocated_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<LeasePayment, $this>
     */
    public function leasePayment(): BelongsTo
    {
        return $this->belongsTo(LeasePayment::class);
    }

    /**
     * @return BelongsTo<RentInstallment, $this>
     */
    public function rentInstallment(): BelongsTo
    {
        return $this->belongsTo(RentInstallment::class);
    }

    /**
     * @return BelongsTo<TenantFinancialAccount, $this>
     */
    public function tenantFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(TenantFinancialAccount::class);
    }
}
