<?php

namespace App\Models;

use App\Enums\PaymentImportRowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentImportRow extends Model
{
    protected $guarded = ['id', 'status', 'lease_payment_id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'status' => PaymentImportRowStatus::class,
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'raw_payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PaymentImportBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PaymentImportBatch::class, 'payment_import_batch_id');
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
}
