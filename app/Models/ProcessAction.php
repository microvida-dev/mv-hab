<?php

namespace App\Models;

use App\Enums\ProcessActionStatus;
use App\Enums\ProcessActionType;
use Database\Factories\ProcessActionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessAction extends Model
{
    /** @use HasFactory<ProcessActionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['action_type', 'status', 'title', 'description', 'due_at', 'metadata'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action_type' => ProcessActionType::class,
            'status' => ProcessActionStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'action_number';
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

    /** @return MorphTo<Model, $this> */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
