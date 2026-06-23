<?php

namespace App\Models;

use App\Enums\AdministrativeTaskPriority;
use App\Enums\AdministrativeTaskStatus;
use Database\Factories\AdministrativeTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdministrativeTask extends Model
{
    /** @use HasFactory<AdministrativeTaskFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'assigned_to',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdministrativeTaskStatus::class,
            'priority' => AdministrativeTaskPriority::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
