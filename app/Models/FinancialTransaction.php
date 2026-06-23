<?php

namespace App\Models;

use App\Enums\FinancialTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialTransaction extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'transaction_type' => FinancialTransactionType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TenantFinancialAccount, $this>
     */
    public function tenantFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(TenantFinancialAccount::class);
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
