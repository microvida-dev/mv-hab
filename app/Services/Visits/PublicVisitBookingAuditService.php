<?php

declare(strict_types=1);

namespace App\Services\Visits;

use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Models\AuditEvent;
use App\Models\PublicVisitBooking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PublicVisitBookingAuditService
{
    /**
     * @param  array<string, bool|int|string|null>  $metadata
     */
    public function record(
        string $eventCode,
        PublicVisitBooking $booking,
        string $description,
        array $metadata = [],
        ?User $actor = null,
        AuditEventSeverity $severity = AuditEventSeverity::Info,
    ): AuditEvent {
        $request = app()->bound('request')
            ? app(Request::class)
            : null;

        return AuditEvent::query()->create([
            'event_number' => 'AUD-'
                .now()->format('YmdHis')
                .'-'
                .Str::upper(Str::random(8)),
            'municipality_id' => $booking->municipality_id,
            'user_id' => $actor?->id,
            'event_code' => $eventCode,
            'event_category' => AuditEventCategory::Workflow,
            'severity' => $severity,
            'auditable_type' => $booking->getMorphClass(),
            'auditable_id' => $booking->getKey(),
            'request_method' => $request?->method(),
            // Never persist the cancellation token contained in public URLs.
            'request_path' => null,
            'route_name' => $request?->route()?->getName(),
            'description' => $description,
            'metadata' => $metadata === [] ? null : $metadata,
            'occurred_at' => now(),
        ]);
    }
}
