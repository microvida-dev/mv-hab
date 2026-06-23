<?php

namespace App\Models;

use App\Enums\PublicProcessStatus;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use Database\Factories\ProcessTimelineEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property TimelineEventType $event_type
 * @property TimelineEventVisibility $visibility
 * @property PublicProcessStatus|null $public_status
 */
class ProcessTimelineEvent extends Model
{
    /** @use HasFactory<ProcessTimelineEventFactory> */
    use HasFactory;

    protected $fillable = [
        'event_type',
        'visibility',
        'public_status',
        'title',
        'description',
        'occurred_at',
        'due_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => TimelineEventType::class,
            'visibility' => TimelineEventVisibility::class,
            'public_status' => PublicProcessStatus::class,
            'occurred_at' => 'datetime',
            'due_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'event_number';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<AdhesionRegistration, $this> */
    public function adhesionRegistration(): BelongsTo
    {
        return $this->belongsTo(AdhesionRegistration::class);
    }

    /** @return BelongsTo<Contest, $this> */
    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    /** @return BelongsTo<HousingUnit, $this> */
    public function housingUnit(): BelongsTo
    {
        return $this->belongsTo(HousingUnit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return MorphTo<Model, $this> */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
