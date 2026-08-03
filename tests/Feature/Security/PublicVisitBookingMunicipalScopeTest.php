<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\PublicVisitBookingStatus;
use App\Enums\VisitSlotStatus;
use App\Models\HousingUnit;
use App\Models\Municipality;
use App\Models\PublicVisitBooking;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PublicVisitBookingMunicipalScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_backoffice_public_bookings_are_isolated_by_municipality(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $staffA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $staffA->assignRole('municipal_technician');
        $bookingA = $this->bookingFor($municipalityA, 'PVB-MUNICIPAL-A');
        $bookingB = $this->bookingFor($municipalityB, 'PVB-MUNICIPAL-B');

        $this->actingAs($staffA)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-visit-bookings.index'))
            ->assertOk()
            ->assertSee($bookingA->booking_reference)
            ->assertDontSee($bookingB->booking_reference);

        $this->actingAs($staffA)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-visit-bookings.show',
                $bookingB,
            ))
            ->assertForbidden();
    }

    public function test_sensitive_contact_view_is_minimally_audited(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $municipality = Municipality::factory()->create();
        $staff = User::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $staff->assignRole('municipal_technician');
        $booking = $this->bookingFor(
            $municipality,
            'PVB-SENSITIVE-VIEW',
        );

        $this->actingAs($staff)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.public-visit-bookings.show',
                $booking,
            ))
            ->assertOk()
            ->assertSee('visitante@example.test');

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'public_visit_booking_sensitive_viewed',
            'municipality_id' => $municipality->id,
            'user_id' => $staff->id,
            'auditable_id' => $booking->id,
            'ip_address' => null,
            'user_agent' => null,
        ]);
    }

    public function test_mutations_require_local_scope_and_fail_closed_without_municipality(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $staffA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $staffA->assignRole('municipal_technician');
        $withoutMunicipality = User::factory()
            ->withoutMunicipality()
            ->create();
        $withoutMunicipality->assignRole('municipal_technician');
        $bookingA = $this->bookingFor($municipalityA, 'PVB-CANCEL-A');
        $bookingB = $this->bookingFor($municipalityB, 'PVB-CANCEL-B');

        $this->actingAs($staffA)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.public-visit-bookings.cancel',
                $bookingB,
            ))
            ->assertForbidden();

        $this->actingAs($withoutMunicipality)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.public-visit-bookings.index'))
            ->assertForbidden();

        $this->actingAs($staffA)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route(
                'backoffice.public-visit-bookings.cancel',
                $bookingA,
            ))
            ->assertRedirect(route(
                'backoffice.public-visit-bookings.show',
                $bookingA,
            ));

        $this->assertSame(
            PublicVisitBookingStatus::Cancelled,
            $bookingA->fresh()->status,
        );
        $this->assertSame(0, $bookingA->slot->fresh()->booked_count);
        $this->assertSame(
            PublicVisitBookingStatus::Booked,
            $bookingB->fresh()->status,
        );
    }

    private function bookingFor(
        Municipality $municipality,
        string $reference,
    ): PublicVisitBooking {
        $housingUnit = HousingUnit::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $availability = VisitAvailability::factory()->create([
            'municipality_id' => $municipality->id,
            'housing_unit_id' => $housingUnit->id,
            'contest_id' => null,
            'is_active' => true,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addHours(2),
        ]);
        $slot = VisitSlot::factory()->create([
            'municipality_id' => $municipality->id,
            'visit_availability_id' => $availability->id,
            'housing_unit_id' => $housingUnit->id,
            'contest_id' => null,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addMinutes(30),
            'capacity' => 3,
            'booked_count' => 1,
            'status' => VisitSlotStatus::Reserved,
        ]);
        $token = Str::random(64);
        $booking = new PublicVisitBooking;
        $booking->forceFill([
            'booking_reference' => $reference,
            'municipality_id' => $municipality->id,
            'visit_slot_id' => $slot->id,
            'housing_unit_id' => $housingUnit->id,
            'contest_id' => null,
            'status' => PublicVisitBookingStatus::Booked,
            'contact_name' => 'Pessoa Visitante',
            'contact_email' => 'visitante@example.test',
            'contact_phone' => null,
            'email_hash' => hash('sha256', $reference.'|email'),
            'active_fingerprint' => hash(
                'sha256',
                $reference.'|active',
            ),
            'guest_count' => 1,
            'cancellation_token_hash' => hash('sha256', $token),
            'cancellation_token' => $token,
            'cancellation_token_expires_at' => $slot->starts_at,
            'privacy_notice_accepted_at' => now(),
            'privacy_notice_version' => '2026-07-30',
            'booking_source' => 'public_portal',
            'booked_at' => now(),
            'retention_due_at' => now()->addMonths(6),
        ])->save();

        return $booking->refresh();
    }
}
