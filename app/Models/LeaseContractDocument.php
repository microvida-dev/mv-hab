<?php

namespace App\Models;

use App\Enums\ContractDocumentStatus;
use App\Enums\ContractDocumentType;
use Database\Factories\LeaseContractDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaseContractDocument extends Model
{
    /** @use HasFactory<LeaseContractDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'storage_path', 'checksum', 'file_size', 'generated_by', 'generated_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => ContractDocumentStatus::class,
            'document_type' => ContractDocumentType::class,
            'generated_at' => 'datetime',
            'issued_at' => 'datetime',
            'signed_at' => 'datetime',
            'archived_at' => 'datetime',
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
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
