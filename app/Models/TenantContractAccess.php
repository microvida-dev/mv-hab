<?php

namespace App\Models;

use App\Enums\ContractOccupancyStatus;
use Database\Factories\TenantContractAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantContractAccess extends Model
{
    /** @use HasFactory<TenantContractAccessFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'granted_at', 'revoked_at', 'granted_by', 'revoked_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ContractOccupancyStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<TenantProfile, $this> */
    public function tenantProfile(): BelongsTo
    {
        return $this->belongsTo(TenantProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
