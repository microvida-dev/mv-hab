<?php

namespace App\Models;

use App\Enums\CommunicationReceiptType;
use Database\Factories\CommunicationReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunicationReceipt extends Model
{
    /** @use HasFactory<CommunicationReceiptFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [
        'id',
        'receipt_number',
        'storage_disk',
        'storage_path',
        'mime_type',
        'file_size',
        'checksum',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'receipt_type' => CommunicationReceiptType::class,
            'generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CommunicationLog, $this>
     */
    public function communication(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class, 'communication_log_id');
    }

    /**
     * @return BelongsTo<CommunicationDelivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(CommunicationDelivery::class, 'communication_delivery_id');
    }
}
