<?php

namespace App\Models;

use App\Enums\ContractPartyType;
use Database\Factories\LeaseContractPartyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaseContractParty extends Model
{
    /** @use HasFactory<LeaseContractPartyFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return ['party_type' => ContractPartyType::class];
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
