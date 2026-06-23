<?php

namespace App\Models;

use App\Enums\IncomeChangeStatus;
use App\Enums\IncomeChangeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeChangeDeclaration extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'status', 'submitted_at', 'reviewed_at', 'reviewed_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => IncomeChangeStatus::class,
            'change_type' => IncomeChangeType::class,
            'changed_at' => 'date',
            'monthly_income_before' => 'decimal:2',
            'monthly_income_after' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<RentReview, $this>
     */
    public function rentReview(): BelongsTo
    {
        return $this->belongsTo(RentReview::class);
    }
}
