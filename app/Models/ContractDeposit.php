<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Database\Factories\ContractDepositFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property DepositStatus $status
 */
class ContractDeposit extends Model
{
    /** @use HasFactory<ContractDepositFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'status', 'paid_at', 'waived_at', 'cancelled_at', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => DepositStatus::class,
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'requested_at' => 'datetime',
            'paid_at' => 'datetime',
            'waived_at' => 'datetime',
            'refunded_at' => 'datetime',
            'retained_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Contract, $this> */
    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'lease_contract_id');
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<Allocation, $this> */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
