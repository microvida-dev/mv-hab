<?php

namespace App\Models;

use App\Enums\DefaultNoticeStatus;
use App\Enums\DefaultNoticeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DefaultNotice extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'notice_number', 'status', 'issued_at', 'acknowledged_at', 'cancelled_at', 'candidate_visible', 'created_by', 'issued_by', 'cancelled_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => DefaultNoticeStatus::class,
            'notice_type' => DefaultNoticeType::class,
            'amount_due' => 'decimal:2',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'candidate_visible' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Arrear, $this>
     */
    public function arrear(): BelongsTo
    {
        return $this->belongsTo(Arrear::class);
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
}
