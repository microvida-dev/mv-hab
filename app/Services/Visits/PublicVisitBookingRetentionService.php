<?php

declare(strict_types=1);

namespace App\Services\Visits;

use App\Models\PublicVisitBooking;
use Illuminate\Support\Facades\DB;

final class PublicVisitBookingRetentionService
{
    public function __construct(
        private readonly PublicVisitBookingAuditService $audit,
    ) {}

    public function dueCount(): int
    {
        return PublicVisitBooking::query()
            ->whereNull('anonymized_at')
            ->where('retention_due_at', '<=', now())
            ->count();
    }

    public function anonymizeDue(int $limit = 500): int
    {
        $ids = PublicVisitBooking::query()
            ->whereNull('anonymized_at')
            ->where('retention_due_at', '<=', now())
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->pluck('id');
        $affected = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$affected): void {
                $booking = PublicVisitBooking::query()
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (! $booking instanceof PublicVisitBooking
                    || $booking->anonymized_at !== null
                    || $booking->retention_due_at?->isFuture()) {
                    return;
                }

                $booking->forceFill([
                    'contact_name' => null,
                    'contact_email' => null,
                    'contact_phone' => null,
                    'email_hash' => str_repeat('0', 64),
                    'active_fingerprint' => null,
                    'cancellation_token_hash' => hash(
                        'sha256',
                        'anonymized|'.$booking->getKey().'|'.now()->toISOString(),
                    ),
                    'cancellation_token' => null,
                    'status_notes' => null,
                    'confirmation_error_code' => null,
                    'anonymized_at' => now(),
                ])->save();

                $this->audit->record(
                    'public_visit_booking_anonymized',
                    $booking,
                    'Dados pessoais da marcação pública anonimizados por retenção.',
                    [
                        'retention_due_at' => $booking
                            ->retention_due_at
                            ?->toISOString(),
                    ],
                );

                $affected++;
            }, 3);
        }

        return $affected;
    }
}
