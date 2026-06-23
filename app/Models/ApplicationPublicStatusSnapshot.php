<?php

namespace App\Models;

use App\Enums\PublicProcessStatus;
use Database\Factories\ApplicationPublicStatusSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationPublicStatusSnapshot extends Model
{
    /** @use HasFactory<ApplicationPublicStatusSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'application_id',
        'public_status',
        'internal_status',
        'title',
        'description',
        'next_step',
        'action_required',
        'action_due_at',
        'progress_percentage',
        'is_terminal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'public_status' => PublicProcessStatus::class,
            'action_required' => 'boolean',
            'action_due_at' => 'datetime',
            'progress_percentage' => 'integer',
            'is_terminal' => 'boolean',
        ];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
