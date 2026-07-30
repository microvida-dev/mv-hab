<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AuditEventSeverity;
use App\Mail\PublicVisitBookingConfirmationMail;
use App\Models\PublicVisitBooking;
use App\Services\Visits\PublicVisitBookingAuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DeliverPublicVisitBookingConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public readonly int $bookingId,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(
        PublicVisitBookingAuditService $audit,
    ): void {
        $booking = PublicVisitBooking::query()
            ->with(['slot', 'housingUnit', 'municipality'])
            ->find($this->bookingId);

        if (! $booking instanceof PublicVisitBooking
            || ! $booking->isActive()
            || $booking->confirmation_sent_at !== null
            || ! is_string($booking->contact_email)
            || $booking->contact_email === ''
            || ! is_string($booking->cancellation_token)
            || $booking->cancellation_token === '') {
            return;
        }

        Mail::to($booking->contact_email)->send(
            new PublicVisitBookingConfirmationMail(
                $booking,
                $booking->cancellation_token,
            ),
        );

        $booking->forceFill([
            'confirmation_sent_at' => now(),
            'confirmation_failed_at' => null,
            'confirmation_error_code' => null,
            'cancellation_token' => null,
        ])->save();

        $audit->record(
            'public_visit_booking_confirmation_delivered',
            $booking,
            'Confirmação da marcação pública entregue por email.',
            [
                'channel' => 'email',
                'visit_slot_id' => (int) $booking->visit_slot_id,
            ],
        );
    }

    public function failed(Throwable $exception): void
    {
        $booking = PublicVisitBooking::query()->find($this->bookingId);

        if (! $booking instanceof PublicVisitBooking) {
            return;
        }

        $booking->forceFill([
            'confirmation_failed_at' => now(),
            'confirmation_error_code' => class_basename($exception),
        ])->save();

        app(PublicVisitBookingAuditService::class)->record(
            'public_visit_booking_confirmation_failed',
            $booking,
            'Falha definitiva na entrega da confirmação da marcação pública.',
            [
                'channel' => 'email',
                'error_code' => class_basename($exception),
            ],
            severity: AuditEventSeverity::Warning,
        );
    }
}
