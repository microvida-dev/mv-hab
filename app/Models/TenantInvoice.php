<?php

namespace App\Models;

use App\Enums\ChargeType;
use App\Enums\TenantInvoiceStatus;
use Database\Factories\TenantInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantInvoice extends Model
{
    /** @use HasFactory<TenantInvoiceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'invoice_number', 'status', 'amount_paid', 'amount_outstanding', 'paid_at', 'cancelled_at', 'voided_at', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => TenantInvoiceStatus::class,
            'charge_type' => ChargeType::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'original_amount' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_outstanding' => 'decimal:2',
            'issued_at' => 'datetime',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'voided_at' => 'datetime',
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

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<RentInstallment, $this> */
    public function sourceRentInstallment(): BelongsTo
    {
        return $this->belongsTo(RentInstallment::class, 'source_rent_installment_id');
    }

    /** @return HasMany<TenantPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }
}
