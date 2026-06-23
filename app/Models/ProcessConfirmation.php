<?php

namespace App\Models;

use App\Enums\ProcessConfirmationStatus;
use Database\Factories\ProcessConfirmationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $process_number
 */
class ProcessConfirmation extends Model
{
    /** @use HasFactory<ProcessConfirmationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProcessConfirmationStatus::class,
            'payload' => 'array',
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'confirmation_number';
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Contest, $this>
     */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
