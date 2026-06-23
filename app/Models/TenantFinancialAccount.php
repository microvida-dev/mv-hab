<?php

namespace App\Models;

use App\Enums\FinancialAccountStatus;
use Database\Factories\TenantFinancialAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantFinancialAccount extends Model
{
    /** @use HasFactory<TenantFinancialAccountFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'account_number', 'status', 'current_balance', 'total_issued', 'total_paid', 'total_overdue', 'total_waived', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => FinancialAccountStatus::class,
            'opened_at' => 'datetime',
            'suspended_at' => 'datetime',
            'closed_at' => 'datetime',
            'current_balance' => 'decimal:2',
            'total_issued' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'total_overdue' => 'decimal:2',
            'total_waived' => 'decimal:2',
            'next_due_date' => 'date',
        ];
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<Allocation, $this> */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
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

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return HasOne<RentSchedule, $this> */
    public function activeSchedule(): HasOne
    {
        return $this->hasOne(RentSchedule::class)->where('status', 'active')->latestOfMany();
    }

    /** @return HasMany<RentSchedule, $this> */
    public function rentSchedules(): HasMany
    {
        return $this->hasMany(RentSchedule::class);
    }

    /** @return HasMany<RentInstallment, $this> */
    public function rentInstallments(): HasMany
    {
        return $this->hasMany(RentInstallment::class);
    }

    /** @return HasMany<LeasePayment, $this> */
    public function leasePayments(): HasMany
    {
        return $this->hasMany(LeasePayment::class);
    }

    /** @return HasMany<TenantInvoice, $this> */
    public function tenantInvoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class);
    }

    /** @return HasMany<TenantPayment, $this> */
    public function tenantPayments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    /** @return HasMany<PaymentReceipt, $this> */
    public function paymentReceipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    /** @return HasMany<FinancialTransaction, $this> */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /** @return HasMany<Arrear, $this> */
    public function arrears(): HasMany
    {
        return $this->hasMany(Arrear::class);
    }

    /** @return HasMany<RegularizationAgreement, $this> */
    public function regularizationAgreements(): HasMany
    {
        return $this->hasMany(RegularizationAgreement::class);
    }

    /** @return HasMany<RentReview, $this> */
    public function rentReviews(): HasMany
    {
        return $this->hasMany(RentReview::class);
    }
}
