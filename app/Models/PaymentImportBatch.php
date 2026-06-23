<?php

namespace App\Models;

use App\Enums\PaymentImportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentImportBatch extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'batch_number', 'status', 'row_count', 'matched_count', 'imported_count', 'failed_count', 'processed_at', 'reversed_at', 'created_by', 'processed_by', 'reversed_by', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'status' => PaymentImportStatus::class,
            'processed_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PaymentImportRow, $this>
     */
    public function rows(): HasMany
    {
        return $this->hasMany(PaymentImportRow::class);
    }
}
