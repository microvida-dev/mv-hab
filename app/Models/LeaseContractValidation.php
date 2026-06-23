<?php

namespace App\Models;

use App\Enums\ContractValidationStatus;
use App\Enums\ContractValidationType;
use Database\Factories\LeaseContractValidationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ContractValidationStatus $status
 * @property ContractValidationType $validation_type
 */
class LeaseContractValidation extends Model
{
    /** @use HasFactory<LeaseContractValidationFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'validated_by', 'validated_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ContractValidationStatus::class,
            'validation_type' => ContractValidationType::class,
            'validated_at' => 'datetime',
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
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
