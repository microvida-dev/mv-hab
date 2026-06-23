<?php

namespace App\Models;

use App\Enums\ArrearStatus;
use Database\Factories\ArrearFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arrear extends Model
{
    /** @use HasFactory<ArrearFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'detected_at', 'notified_at', 'regularized_at', 'waived_at', 'closed_at', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ArrearStatus::class,
            'original_amount' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'overdue_since' => 'date',
            'detected_at' => 'datetime',
            'notified_at' => 'datetime',
            'regularized_at' => 'datetime',
            'waived_at' => 'datetime',
            'closed_at' => 'datetime',
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
     * @return BelongsTo<RentInstallment, $this>
     */
    public function rentInstallment(): BelongsTo
    {
        return $this->belongsTo(RentInstallment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<RegularizationAgreement, $this>
     */
    public function regularizationAgreement(): BelongsTo
    {
        return $this->belongsTo(RegularizationAgreement::class);
    }

    /**
     * @return HasMany<DefaultNotice, $this>
     */
    public function defaultNotices(): HasMany
    {
        return $this->hasMany(DefaultNotice::class);
    }
}
