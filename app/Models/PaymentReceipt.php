<?php

namespace App\Models;

use App\Enums\PaymentReceiptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentReceipt extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'receipt_number', 'status', 'issued_at', 'cancelled_at', 'reissued_at', 'issued_by', 'cancelled_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => PaymentReceiptStatus::class,
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reissued_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<LeasePayment, $this> */
    public function leasePayment(): BelongsTo
    {
        return $this->belongsTo(LeasePayment::class);
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
}
