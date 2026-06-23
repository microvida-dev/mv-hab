<?php

namespace App\Models;

use App\Enums\ContractSignatureMethod;
use App\Enums\ContractSignatureRole;
use App\Enums\ContractSignatureStatus;
use Database\Factories\LeaseContractSignatureFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ContractSignatureRole $signature_role
 * @property ContractSignatureStatus $status
 * @property ContractSignatureMethod $signature_method
 */
class LeaseContractSignature extends Model
{
    /** @use HasFactory<LeaseContractSignatureFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'signed_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'signature_role' => ContractSignatureRole::class,
            'status' => ContractSignatureStatus::class,
            'signature_method' => ContractSignatureMethod::class,
            'signed_at' => 'datetime',
        ];
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
