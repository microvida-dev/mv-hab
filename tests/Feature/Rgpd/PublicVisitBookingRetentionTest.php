<?php

declare(strict_types=1);

namespace Tests\Feature\Rgpd;

use App\Enums\PublicVisitBookingStatus;
use App\Models\HousingUnit;
use App\Models\PublicVisitBooking;
use App\Models\VisitSlot;
use App\Services\Visits\PublicVisitBookingRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVisitBookingRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_public_visit_personal_data_is_anonymized(): void
    {
        $unit = HousingUnit::factory()->publiclyVisible()->create();
        $slot = VisitSlot::factory()->create([
            'municipality_id' => $unit->municipality_id,
            'housing_unit_id' => $unit->id,
        ]);
        $booking = new PublicVisitBooking;
        $booking->forceFill([
            'booking_reference' => 'PVB-RETENTION-1',
            'municipality_id' => $unit->municipality_id,
            'visit_slot_id' => $slot->id,
            'housing_unit_id' => $unit->id,
            'status' => PublicVisitBookingStatus::Cancelled,
            'contact_name' => 'Nome pessoal',
            'contact_email' => 'retention@example.test',
            'contact_phone' => '912345678',
            'email_hash' => hash('sha256', 'email'),
            'active_fingerprint' => null,
            'guest_count' => 1,
            'cancellation_token_hash' => hash('sha256', 'token'),
            'cancellation_token' => null,
            'cancellation_token_expires_at' => now()->subDay(),
            'privacy_notice_accepted_at' => now()->subMonths(7),
            'privacy_notice_version' => '1',
            'booking_source' => 'public_portal',
            'booked_at' => now()->subMonths(7),
            'retention_due_at' => now()->subDay(),
        ])->save();

        $affected = app(
            PublicVisitBookingRetentionService::class,
        )->anonymizeDue();

        $this->assertSame(1, $affected);
        $booking->refresh();
        $this->assertNull($booking->contact_name);
        $this->assertNull($booking->contact_email);
        $this->assertNull($booking->contact_phone);
        $this->assertNotNull($booking->anonymized_at);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'public_visit_booking_anonymized',
            'auditable_id' => $booking->id,
        ]);
    }

    public function test_public_visit_booking_cannot_be_physically_deleted(): void
    {
        $unit = HousingUnit::factory()->publiclyVisible()->create();
        $slot = VisitSlot::factory()->create([
            'municipality_id' => $unit->municipality_id,
            'housing_unit_id' => $unit->id,
        ]);
        $booking = new PublicVisitBooking;
        $booking->forceFill([
            'booking_reference' => 'PVB-RETENTION-IMMUTABLE',
            'municipality_id' => $unit->municipality_id,
            'visit_slot_id' => $slot->id,
            'housing_unit_id' => $unit->id,
            'status' => PublicVisitBookingStatus::Cancelled,
            'contact_name' => null,
            'contact_email' => null,
            'contact_phone' => null,
            'email_hash' => hash('sha256', 'immutable-email'),
            'active_fingerprint' => null,
            'guest_count' => 1,
            'cancellation_token_hash' => hash(
                'sha256',
                'immutable-token',
            ),
            'cancellation_token' => null,
            'cancellation_token_expires_at' => now()->subDay(),
            'privacy_notice_accepted_at' => now()->subMonths(7),
            'privacy_notice_version' => '1',
            'booking_source' => 'public_portal',
            'booked_at' => now()->subMonths(7),
            'retention_due_at' => now()->subDay(),
            'anonymized_at' => now(),
        ])->save();

        $this->assertFalse($booking->delete());
        $this->assertDatabaseHas('public_visit_bookings', [
            'id' => $booking->id,
        ]);
    }
}
