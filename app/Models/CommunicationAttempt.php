<?php

namespace App\Models;

use App\Enums\CommunicationAttemptStatus;
use Database\Factories\CommunicationAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationAttempt extends Model
{
    /** @use HasFactory<CommunicationAttemptFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id', 'attempt_number', 'status', 'started_at', 'finished_at', 'created_by', 'created_at'];

    protected function casts(): array
    {
        return [
            'status' => CommunicationAttemptStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CommunicationDelivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(CommunicationDelivery::class, 'communication_delivery_id');
    }
}
