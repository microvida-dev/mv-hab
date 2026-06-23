<?php

namespace App\Models;

use App\Enums\AnnualDocumentUpdateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualDocumentUpdateRequest extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'request_number', 'status', 'requested_at', 'submitted_at', 'reviewed_at', 'closed_at', 'requested_by', 'reviewed_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => AnnualDocumentUpdateStatus::class,
            'due_date' => 'date',
            'requested_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'closed_at' => 'datetime',
            'required_document_types' => 'array',
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
     * @return HasMany<AnnualDocumentUpdateSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AnnualDocumentUpdateSubmission::class);
    }
}
