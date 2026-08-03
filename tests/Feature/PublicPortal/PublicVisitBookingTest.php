<?php

declare(strict_types=1);

namespace Tests\Feature\PublicPortal;

use App\Enums\PublicVisitBookingStatus;
use App\Enums\VisitSlotStatus;
use App\Jobs\DeliverPublicVisitBookingConfirmation;
use App\Models\AuditEvent;
use App\Models\HousingUnit;
use App\Models\PublicVisitBooking;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublicVisitBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_book_public_visit_without_application_or_authentication(): void
    {
        Queue::fake();
        [$housingUnit, $slot] = $this->publicSlot(capacity: 4);

        $response = $this->post(route(
            'public.visit-bookings.store',
            $housingUnit->public_slug,
        ), [
            'visit_slot_id' => $slot->id,
            'name' => 'Pessoa Visitante',
            'email' => 'visitante@example.test',
            'phone' => '+351 912 345 678',
            'guest_count' => 2,
            'privacy_accepted' => '1',
            'website' => '',
        ]);

        $response->assertRedirect(
            route('public.visit-bookings.confirmed'),
        );

        $booking = PublicVisitBooking::query()->firstOrFail();
        $this->assertSame(PublicVisitBookingStatus::Booked, $booking->status);
        $this->assertSame($housingUnit->id, $booking->housing_unit_id);
        $this->assertSame($slot->id, $booking->visit_slot_id);
        $this->assertSame(2, $booking->guest_count);
        $this->assertSame('visitante@example.test', $booking->contact_email);
        $this->assertDatabaseCount('housing_visits', 0);
        $this->assertSame(2, $slot->fresh()->booked_count);
        $this->assertSame(
            VisitSlotStatus::Reserved,
            $slot->fresh()->status,
        );

        $raw = DB::table('public_visit_bookings')
            ->where('id', $booking->id)
            ->first();
        $this->assertNotSame('Pessoa Visitante', $raw->contact_name);
        $this->assertNotSame('visitante@example.test', $raw->contact_email);
        $this->assertNull($raw->ip_address ?? null);
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'public_visit_booking_created',
            'municipality_id' => $housingUnit->municipality_id,
        ]);
        Queue::assertPushed(DeliverPublicVisitBookingConfirmation::class);
    }

    public function test_capacity_is_revalidated_and_prevents_overbooking(): void
    {
        Queue::fake();
        [$housingUnit, $slot] = $this->publicSlot(capacity: 2);

        $this->post(route(
            'public.visit-bookings.store',
            $housingUnit->public_slug,
        ), $this->payload($slot, 'one@example.test', 2))
            ->assertRedirect();

        $this->post(route(
            'public.visit-bookings.store',
            $housingUnit->public_slug,
        ), $this->payload($slot, 'two@example.test', 1))
            ->assertSessionHasErrors('guest_count');

        $this->assertDatabaseCount('public_visit_bookings', 1);
        $this->assertSame(2, $slot->fresh()->booked_count);
        $this->assertSame(VisitSlotStatus::Full, $slot->fresh()->status);
    }

    public function test_same_email_cannot_hold_two_active_bookings_for_same_slot(): void
    {
        Queue::fake();
        [$housingUnit, $slot] = $this->publicSlot(capacity: 3);

        $this->post(route(
            'public.visit-bookings.store',
            $housingUnit->public_slug,
        ), $this->payload($slot, 'repeat@example.test'))
            ->assertRedirect();

        $this->post(route(
            'public.visit-bookings.store',
            $housingUnit->public_slug,
        ), $this->payload($slot, 'REPEAT@example.test'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('public_visit_bookings', 1);
        $this->assertSame(1, $slot->fresh()->booked_count);
    }

    public function test_public_cancellation_is_idempotent_and_releases_capacity(): void
    {
        Queue::fake();
        [$housingUnit, $slot] = $this->publicSlot(capacity: 3);

        $this->post(route(
            'public.visit-bookings.store',
            $housingUnit->public_slug,
        ), $this->payload($slot, 'cancel@example.test', 2));

        $confirmation = session('public_visit_booking_confirmation');
        $token = basename((string) $confirmation['cancellation_url']);

        $this->post(route(
            'public.visit-bookings.destroy',
            ['token' => $token],
        ))->assertRedirect(route('public.visit-bookings.cancelled'));

        $booking = PublicVisitBooking::query()->firstOrFail();
        $this->assertSame(
            PublicVisitBookingStatus::Cancelled,
            $booking->status,
        );
        $this->assertSame(0, $slot->fresh()->booked_count);
        $this->assertSame(
            VisitSlotStatus::Available,
            $slot->fresh()->status,
        );

        $this->post(route(
            'public.visit-bookings.destroy',
            ['token' => $token],
        ))->assertRedirect(route('public.visit-bookings.cancelled'));
        $this->assertSame(0, $slot->fresh()->booked_count);
        $this->assertSame(
            1,
            AuditEvent::query()
                ->where('event_code', 'public_visit_booking_cancelled')
                ->count(),
        );
        $audit = AuditEvent::query()
            ->where('event_code', 'public_visit_booking_cancelled')
            ->firstOrFail();
        $this->assertNull($audit->request_path);
        $this->assertStringNotContainsString(
            $token,
            json_encode($audit->metadata, JSON_THROW_ON_ERROR),
        );
    }

    public function test_booking_rejects_slot_from_another_housing_unit(): void
    {
        Queue::fake();
        [$housingUnit] = $this->publicSlot();
        [, $otherSlot] = $this->publicSlot();

        $this->post(route(
            'public.visit-bookings.store',
            $housingUnit->public_slug,
        ), $this->payload($otherSlot, 'wrong@example.test'))
            ->assertSessionHasErrors('visit_slot_id');

        $this->assertDatabaseCount('public_visit_bookings', 0);
    }

    public function test_internal_rate_limit_uses_minimized_email_and_ip_fingerprint(): void
    {
        Queue::fake();
        config()->set('public_visits.rate_limit.attempts', 1);
        config()->set('public_visits.rate_limit.decay_seconds', 600);
        [$firstUnit, $firstSlot] = $this->publicSlot();
        [$secondUnit, $secondSlot] = $this->publicSlot();

        $this->post(route(
            'public.visit-bookings.store',
            $firstUnit->public_slug,
        ), $this->payload($firstSlot, 'limited@example.test'))
            ->assertRedirect();

        $this->post(route(
            'public.visit-bookings.store',
            $secondUnit->public_slug,
        ), $this->payload($secondSlot, 'limited@example.test'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('public_visit_bookings', 1);
    }

    /**
     * @return array{HousingUnit, VisitSlot}
     */
    private function publicSlot(int $capacity = 3): array
    {
        $housingUnit = HousingUnit::factory()->publiclyVisible()->create();
        $availability = VisitAvailability::factory()->create([
            'municipality_id' => $housingUnit->municipality_id,
            'housing_unit_id' => $housingUnit->id,
            'contest_id' => null,
            'is_active' => true,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(2),
        ]);
        $slot = VisitSlot::factory()->create([
            'municipality_id' => $housingUnit->municipality_id,
            'visit_availability_id' => $availability->id,
            'housing_unit_id' => $housingUnit->id,
            'contest_id' => null,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addMinutes(30),
            'capacity' => $capacity,
            'booked_count' => 0,
            'status' => VisitSlotStatus::Available,
        ]);

        return [$housingUnit, $slot];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(
        VisitSlot $slot,
        string $email,
        int $guestCount = 1,
    ): array {
        return [
            'visit_slot_id' => $slot->id,
            'name' => 'Pessoa Visitante',
            'email' => $email,
            'phone' => null,
            'guest_count' => $guestCount,
            'privacy_accepted' => '1',
            'website' => '',
        ];
    }
}
