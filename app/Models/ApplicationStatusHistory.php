<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ApplicationStatus|null $from_status
 * @property ApplicationStatus $to_status
 */
class ApplicationStatusHistory extends Model
{
    /** @use HasFactory<ApplicationStatusHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'from_status',
        'to_status',
        'changed_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ApplicationStatus::class,
            'to_status' => ApplicationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
