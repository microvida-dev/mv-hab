<?php

namespace App\Models;

use Database\Factories\LeaseContractClauseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseContractClause extends Model
{
    /** @use HasFactory<LeaseContractClauseFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /**
     * @return BelongsTo<ContractClause, $this>
     */
    public function contractClause(): BelongsTo
    {
        return $this->belongsTo(ContractClause::class);
    }
}
