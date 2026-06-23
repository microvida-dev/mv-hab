<?php

namespace App\Models;

use App\Enums\ContractStatus;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $housing_unit_id
 * @property ContractStatus $status
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 */
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'contract_number',
        'status',
        'monthly_rent',
        'deposit_amount',
        'allocation_id',
        'rent_calculation_id',
        'issued_at',
        'issued_by',
        'signed_at',
        'signed_by',
        'activated_at',
        'activated_by',
        'suspended_at',
        'terminated_at',
        'renewed_at',
        'cancelled_at',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_rent' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'housing_area' => 'decimal:2',
            'renewal_allowed' => 'boolean',
            'status' => ContractStatus::class,
            'issued_at' => 'datetime',
            'signed_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'terminated_at' => 'datetime',
            'renewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Citizen, $this> */
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
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

    /** @return BelongsTo<AllocationOffer, $this> */
    public function allocationOffer(): BelongsTo
    {
        return $this->belongsTo(AllocationOffer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<ContestHousingUnit, $this> */
    public function contestHousingUnit(): BelongsTo
    {
        return $this->belongsTo(ContestHousingUnit::class);
    }

    /** @return BelongsTo<RentCalculation, $this> */
    public function rentCalculation(): BelongsTo
    {
        return $this->belongsTo(RentCalculation::class);
    }

    /** @return BelongsTo<ContractTemplate, $this> */
    public function contractTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return BelongsTo<User, $this> */
    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<LeaseContractParty, $this> */
    public function parties(): HasMany
    {
        return $this->hasMany(LeaseContractParty::class, 'lease_contract_id');
    }

    /** @return HasMany<LeaseContractClause, $this> */
    public function clauses(): HasMany
    {
        return $this->hasMany(LeaseContractClause::class, 'lease_contract_id')->orderBy('sort_order');
    }

    /** @return HasMany<LeaseContractDocument, $this> */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(LeaseContractDocument::class, 'lease_contract_id');
    }

    /** @return HasMany<LeaseContractValidation, $this> */
    public function validations(): HasMany
    {
        return $this->hasMany(LeaseContractValidation::class, 'lease_contract_id')->latest();
    }

    /** @return HasMany<LeaseContractSignature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(LeaseContractSignature::class, 'lease_contract_id')->latest();
    }

    /** @return HasMany<LeaseContractStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(LeaseContractStatusHistory::class, 'lease_contract_id')->latest('created_at');
    }

    /** @return HasOne<ContractDeposit, $this> */
    public function deposit(): HasOne
    {
        return $this->hasOne(ContractDeposit::class, 'lease_contract_id');
    }

    /** @return HasOne<TenantFinancialAccount, $this> */
    public function financialAccount(): HasOne
    {
        return $this->hasOne(TenantFinancialAccount::class, 'lease_contract_id');
    }

    /** @return HasMany<TenantContractAccess, $this> */
    public function tenantContractAccesses(): HasMany
    {
        return $this->hasMany(TenantContractAccess::class, 'lease_contract_id');
    }

    /** @return HasMany<TenantInvoice, $this> */
    public function tenantInvoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class, 'lease_contract_id');
    }

    /** @return HasMany<TenantPayment, $this> */
    public function tenantPayments(): HasMany
    {
        return $this->hasMany(TenantPayment::class, 'lease_contract_id');
    }

    /** @return HasMany<TenantCommunication, $this> */
    public function tenantCommunications(): HasMany
    {
        return $this->hasMany(TenantCommunication::class, 'lease_contract_id');
    }

    /** @return HasMany<RentSchedule, $this> */
    public function rentSchedules(): HasMany
    {
        return $this->hasMany(RentSchedule::class, 'lease_contract_id');
    }

    /** @return HasMany<RentInstallment, $this> */
    public function rentInstallments(): HasMany
    {
        return $this->hasMany(RentInstallment::class, 'lease_contract_id');
    }

    /** @return HasMany<LeasePayment, $this> */
    public function leasePayments(): HasMany
    {
        return $this->hasMany(LeasePayment::class, 'lease_contract_id');
    }

    /** @return HasMany<PaymentReceipt, $this> */
    public function paymentReceipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class, 'lease_contract_id');
    }

    /** @return HasMany<Arrear, $this> */
    public function arrears(): HasMany
    {
        return $this->hasMany(Arrear::class, 'lease_contract_id');
    }

    /** @return HasMany<DefaultNotice, $this> */
    public function defaultNotices(): HasMany
    {
        return $this->hasMany(DefaultNotice::class, 'lease_contract_id');
    }

    /** @return HasMany<RegularizationAgreement, $this> */
    public function regularizationAgreements(): HasMany
    {
        return $this->hasMany(RegularizationAgreement::class, 'lease_contract_id');
    }

    /** @return HasMany<RentReview, $this> */
    public function rentReviews(): HasMany
    {
        return $this->hasMany(RentReview::class, 'lease_contract_id');
    }

    /** @return HasMany<IncomeChangeDeclaration, $this> */
    public function incomeChangeDeclarations(): HasMany
    {
        return $this->hasMany(IncomeChangeDeclaration::class, 'lease_contract_id');
    }

    /** @return HasMany<AnnualDocumentUpdateRequest, $this> */
    public function annualDocumentUpdateRequests(): HasMany
    {
        return $this->hasMany(AnnualDocumentUpdateRequest::class, 'lease_contract_id');
    }

    /** @return HasMany<MaintenanceRequest, $this> */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'lease_contract_id');
    }

    /** @return HasMany<MaintenanceIntervention, $this> */
    public function maintenanceInterventions(): HasMany
    {
        return $this->hasMany(MaintenanceIntervention::class, 'lease_contract_id');
    }

    /** @return HasMany<MaintenanceCost, $this> */
    public function maintenanceCosts(): HasMany
    {
        return $this->hasMany(MaintenanceCost::class, 'lease_contract_id');
    }

    /** @return HasMany<PropertyInspection, $this> */
    public function propertyInspections(): HasMany
    {
        return $this->hasMany(PropertyInspection::class, 'lease_contract_id');
    }

    /** @return HasMany<PropertyHistoryEvent, $this> */
    public function propertyHistoryEvents(): HasMany
    {
        return $this->hasMany(PropertyHistoryEvent::class, 'lease_contract_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeProcessual(Builder $query): Builder
    {
        return $query->whereNotNull('allocation_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForCandidate(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeFinanciallyManageable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ContractStatus::Active,
            ContractStatus::Suspended,
            ContractStatus::Terminated,
            ContractStatus::Renewed,
            ContractStatus::Expired,
            ContractStatus::Ended,
        ]);
    }
}
