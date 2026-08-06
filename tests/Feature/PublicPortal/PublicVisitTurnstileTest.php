<?php

declare(strict_types=1);

namespace Tests\Feature\PublicPortal;

use App\Enums\VisitSlotStatus;
use App\Models\HousingUnit;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PublicVisitTurnstileTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_turnstile_renders_site_key_and_action(): void
    {
        config()->set('public_visits.turnstile.enabled', true);
        config()->set('public_visits.turnstile.site_key', 'site-key');
        config()->set('public_visits.turnstile.secret_key', 'secret');
        config()->set('public_visits.turnstile.expected_hostname', 'hab.microvida.pt');
        config()->set('public_visits.turnstile.action', 'public_visit');
        [$unit] = $this->slot();

        $this->get(route('public.housing-units.show', $unit->public_slug))
            ->assertOk()
            ->assertSee('data-sitekey="site-key"', escape: false)
            ->assertSee('data-action="public_visit"', escape: false);
    }

    public function test_enabled_turnstile_fails_closed_without_valid_response(): void
    {
        Queue::fake();
        config()->set('public_visits.turnstile.enabled', true);
        config()->set('public_visits.turnstile.secret_key', 'secret');
        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        config()->set('public_visits.turnstile.verify_url', $verifyUrl);
        config()->set('public_visits.turnstile.site_key', 'site-key');
        config()->set('public_visits.turnstile.expected_hostname', 'hab.microvida.pt');
        config()->set('public_visits.turnstile.action', 'public_visit');
        Http::fake([
            $verifyUrl => Http::response(['success' => false]),
        ]);
        [$unit, $slot] = $this->slot();

        $this->post(route(
            'public.visit-bookings.store',
            $unit->public_slug,
        ), [
            'visit_slot_id' => $slot->id,
            'name' => 'Visitante',
            'email' => 'turnstile@example.test',
            'guest_count' => 1,
            'privacy_accepted' => '1',
            'challenge_token' => 'invalid',
            'website' => '',
        ])->assertSessionHasErrors('challenge_token');

        $this->assertDatabaseCount('public_visit_bookings', 0);
    }

    public function test_enabled_turnstile_accepts_valid_response(): void
    {
        Queue::fake();
        config()->set('public_visits.turnstile.enabled', true);
        config()->set('public_visits.turnstile.secret_key', 'secret');
        $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        config()->set('public_visits.turnstile.verify_url', $verifyUrl);
        config()->set('public_visits.turnstile.site_key', 'site-key');
        config()->set('public_visits.turnstile.expected_hostname', 'hab.microvida.pt');
        config()->set('public_visits.turnstile.action', 'public_visit');
        Http::fake([
            $verifyUrl => Http::response([
                'success' => true,
                'hostname' => 'hab.microvida.pt',
                'action' => 'public_visit',
            ]),
        ]);
        [$unit, $slot] = $this->slot();

        $this->post(route(
            'public.visit-bookings.store',
            $unit->public_slug,
        ), [
            'visit_slot_id' => $slot->id,
            'name' => 'Visitante',
            'email' => 'turnstile-ok@example.test',
            'guest_count' => 1,
            'privacy_accepted' => '1',
            'challenge_token' => 'valid',
            'website' => '',
        ])->assertRedirect(route('public.visit-bookings.confirmed'));

        $this->assertDatabaseCount('public_visit_bookings', 1);
        Http::assertSent(static fn (Request $request): bool => $request['secret'] === 'secret'
            && $request['response'] === 'valid'
            && is_string($request['idempotency_key']));
    }

    /**
     * @return array{HousingUnit, VisitSlot}
     */
    private function slot(): array
    {
        $unit = HousingUnit::factory()->publiclyVisible()->create();
        $availability = VisitAvailability::factory()->create([
            'municipality_id' => $unit->municipality_id,
            'housing_unit_id' => $unit->id,
            'contest_id' => null,
            'is_active' => true,
        ]);
        $slot = VisitSlot::factory()->create([
            'municipality_id' => $unit->municipality_id,
            'visit_availability_id' => $availability->id,
            'housing_unit_id' => $unit->id,
            'contest_id' => null,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addMinutes(30),
            'capacity' => 2,
            'booked_count' => 0,
            'status' => VisitSlotStatus::Available,
        ]);

        return [$unit, $slot];
    }
}
