<?php

namespace App\Models;

use App\Enums\RegularizationAgreementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularizationAgreement extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'agreement_number', 'status', 'proposed_at', 'approved_at', 'activated_at', 'completed_at', 'breached_at', 'cancelled_at', 'created_by', 'approved_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RegularizationAgreementStatus::class,
            'total_amount' => 'decimal:2',
            'initial_payment_amount' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'proposed_at' => 'datetime',
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'completed_at' => 'datetime',
            'breached_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Arrear, $this>
     */
    public function arrears(): HasMany
    {
        return $this->hasMany(Arrear::class);
    }

    /**
     * @return HasMany<RegularizationInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(RegularizationInstallment::class);
    }
}
