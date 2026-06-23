<?php

namespace App\Models;

use App\Enums\ControlledWithdrawalStatus;
use Database\Factories\ControlledWithdrawalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property ControlledWithdrawalStatus $status
 */
class ControlledWithdrawal extends Model
{
    /** @use HasFactory<ControlledWithdrawalFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['reason', 'consequence_acknowledged'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ControlledWithdrawalStatus::class,
            'consequence_acknowledged' => 'boolean',
            'requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
