<?php

namespace App\Models;

use App\Enums\AdministrativeProcessStatus;
use Database\Factories\AdministrativeProcessStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeProcessStatusHistory extends Model
{
    /** @use HasFactory<AdministrativeProcessStatusHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'from_status',
        'to_status',
        'changed_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => AdministrativeProcessStatus::class,
            'to_status' => AdministrativeProcessStatus::class,
        ];
    }

    /**
     * @return BelongsTo<AdministrativeProcess, $this>
     */
    public function administrativeProcess(): BelongsTo
    {
        return $this->belongsTo(AdministrativeProcess::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
