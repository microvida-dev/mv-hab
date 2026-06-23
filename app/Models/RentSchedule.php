<?php

namespace App\Models;

use App\Enums\RentScheduleStatus;
use Database\Factories\RentScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentSchedule extends Model
{
    /** @use HasFactory<RentScheduleFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'generated_installments_count', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RentScheduleStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'monthly_rent' => 'decimal:2',
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

    /** @return HasMany<RentInstallment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(RentInstallment::class);
    }
}
