<?php

namespace App\Services\Audit;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditTrailService
{
    public function __construct(private readonly AuditEventFormatter $formatter) {}

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $eventCode,
        ?Model $auditable = null,
        AuditEventCategory $category = AuditEventCategory::System,
        AuditEventSeverity $severity = AuditEventSeverity::Info,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?User $subject = null,
        ?Model $related = null,
        ?User $actor = null,
        bool $useAuthenticatedUser = true,
    ): AuditEvent {
        $request = $this->request();

        return AuditEvent::query()->create([
            'event_number' => $this->number(),
            'user_id' => $this->actorId($actor, $useAuthenticatedUser),
            'event_code' => $eventCode,
            'event_category' => $category,
            'severity' => $severity,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'subject_user_id' => $subject?->id,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_method' => $request?->method(),
            'request_path' => $request ? '/'.$request->path() : null,
            'route_name' => $request?->route()?->getName(),
            'description' => $description,
            'old_values' => $oldValues === [] ? null : $this->formatter->mask($oldValues),
            'new_values' => $newValues === [] ? null : $this->formatter->mask($newValues),
            'metadata' => $metadata === [] ? null : $this->formatter->mask($metadata),
            'occurred_at' => now(),
        ]);
    }

    private function actorId(?User $actor, bool $useAuthenticatedUser): ?int
    {
        if ($actor instanceof User) {
            return $actor->id;
        }

        if (! $useAuthenticatedUser) {
            return null;
        }

        $authId = Auth::id();

        if ($authId === null) {
            return null;
        }

        $existingId = User::query()->whereKey($authId)->value('id');

        if (is_int($existingId)) {
            return $existingId;
        }

        return is_numeric($existingId) ? (int) $existingId : null;
    }

    private function number(): string
    {
        return 'AUD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        return app(Request::class);
    }
}
