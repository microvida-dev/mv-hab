<?php

namespace App\Models;

use App\Enums\RegularizationInstallmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegularizationInstallment extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'status', 'paid_at', 'overdue_at', 'waived_at', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => RegularizationInstallmentStatus::class,
            'due_date' => 'date',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'paid_at' => 'datetime',
            'overdue_at' => 'datetime',
            'waived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RegularizationAgreement, $this>
     */
    public function regularizationAgreement(): BelongsTo
    {
        return $this->belongsTo(RegularizationAgreement::class);
    }
}
