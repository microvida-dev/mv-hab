<?php

namespace Tests\Feature\Candidate;

use App\Enums\ContestHousingUnitStatus;
use App\Enums\HousingUnitStatus;
use App\Models\HousingPreference;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Allocation\HousingPreferenceService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompatibleHousingContext;
use Tests\TestCase;

class CompatibleHousingPreferenceTest extends TestCase
{
    use CreatesCompatibleHousingContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_sees_only_compatible_units_with_accessible_order_controls(): void
    {
        $context = $this->compatibleHousingContext();
        $compatible = $this->compatibleContestHousingUnit($context);
        $incompatible = $this->compatibleContestHousingUnit(
            $context,
            monthlyRent: '900.00',
        );

        $response = $this->actingAs($context['candidate'])
            ->get(route(
                'candidate.housing-preferences.edit',
                $context['application'],
            ));

        $response
            ->assertOk()
            ->assertSee('Habitações pretendidas')
            ->assertSee($compatible->housingUnit->public_reference)
            ->assertDontSee($incompatible->housingUnit->public_reference)
            ->assertSee('Selecionar esta habitação')
            ->assertSee('Ordem de preferência')
            ->assertSee('Subir')
            ->assertSee('Descer')
            ->assertSee('A seleção não reserva a habitação')
            ->assertSee('aria-live="polite"', false)
            ->assertSee(':draggable=', false)
            ->assertSee('@keydown.alt.arrow-up.prevent=', false)
            ->assertSee('@keydown.alt.arrow-down.prevent=', false)
            ->assertSee('$nextTick(() => $el.focus())', false)
            ->assertDontSee($compatible->housingUnit->code);
    }

    public function test_empty_state_and_municipal_boundary_do_not_reveal_unavailable_units(): void
    {
        $context = $this->compatibleHousingContext();
        $otherMunicipality = Municipality::factory()->create();
        $foreign = $this->compatibleContestHousingUnit(
            $context,
            municipality: $otherMunicipality,
        );
        $incompatible = $this->compatibleContestHousingUnit(
            $context,
            monthlyRent: '900.00',
        );

        $this->actingAs($context['candidate'])
            ->get(route(
                'candidate.housing-preferences.edit',
                $context['application'],
            ))
            ->assertOk()
            ->assertSee(
                'Não existem habitações compatíveis disponíveis neste momento.',
            )
            ->assertSee('Rever agregado')
            ->assertSee('Rever rendimentos')
            ->assertDontSee($foreign->housingUnit->public_reference)
            ->assertDontSee($incompatible->housingUnit->public_reference);
    }

    public function test_candidate_cannot_access_or_change_another_candidate_application(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        $other = $this->candidateApplicationForHousingContext($context);
        $other['candidate']->assignRole('administrator');

        $this->actingAs($other['candidate'])
            ->get(route(
                'candidate.housing-preferences.edit',
                $context['application'],
            ))
            ->assertForbidden();

        $this->actingAs($other['candidate'])
            ->patch(route(
                'candidate.housing-preferences.update',
                $context['application'],
            ), [
                'preferences' => [[
                    'contest_housing_unit_id' => $unit->id,
                    'preference_order' => 1,
                ]],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('housing_preferences', [
            'application_id' => $context['application']->id,
        ]);
    }

    public function test_policy_uses_application_boundary_without_opening_candidate_routes_to_backoffice(): void
    {
        $context = $this->compatibleHousingContext();
        $admin = User::factory()->create([
            'municipality_id' => $context['municipality']->id,
        ]);
        $admin->assignRole('administrator');
        $context['application']->forceFill([
            'user_id' => $admin->id,
        ])->save();

        $this->assertTrue(
            $admin->can(
                'update',
                [HousingPreference::class, $context['application']->fresh()],
            ),
        );

        $this->actingAs($admin)
            ->get(route(
                'candidate.housing-preferences.edit',
                $context['application'],
            ))
            ->assertForbidden();
    }

    public function test_server_rejects_limits_duplicates_and_non_consecutive_orders(): void
    {
        $context = $this->compatibleHousingContext();
        $units = collect(range(1, 4))
            ->map(fn () => $this->compatibleContestHousingUnit($context));
        $route = route(
            'candidate.housing-preferences.update',
            $context['application'],
        );

        $this->actingAs($context['candidate'])
            ->patch($route, [
                'preferences' => $units
                    ->values()
                    ->map(fn ($unit, int $index): array => [
                        'contest_housing_unit_id' => $unit->id,
                        'preference_order' => $index + 1,
                    ])
                    ->all(),
            ])
            ->assertSessionHasErrors('preferences');

        $this->actingAs($context['candidate'])
            ->patch($route, [
                'preferences' => [
                    [
                        'contest_housing_unit_id' => $units[0]->id,
                        'preference_order' => 1,
                    ],
                    [
                        'contest_housing_unit_id' => $units[0]->id,
                        'preference_order' => 2,
                    ],
                ],
            ])
            ->assertSessionHasErrors(
                'preferences.1.contest_housing_unit_id',
            );

        $this->actingAs($context['candidate'])
            ->patch($route, [
                'preferences' => [
                    [
                        'contest_housing_unit_id' => $units[0]->id,
                        'preference_order' => 1,
                    ],
                    [
                        'contest_housing_unit_id' => $units[1]->id,
                        'preference_order' => 3,
                    ],
                ],
            ])
            ->assertSessionHasErrors('preferences');

        $this->actingAs($context['candidate'])
            ->post(route(
                'candidate.housing-preferences.submit',
                $context['application'],
            ), ['preferences' => []])
            ->assertSessionHasErrors('preferences');

        $this->assertDatabaseCount('housing_preferences', 0);
    }

    public function test_valid_selection_is_ordered_audited_and_does_not_reserve_a_unit(): void
    {
        $context = $this->compatibleHousingContext();
        $first = $this->compatibleContestHousingUnit($context);
        $second = $this->compatibleContestHousingUnit($context);

        $this->actingAs($context['candidate'])
            ->patch(route(
                'candidate.housing-preferences.update',
                $context['application'],
            ), [
                'preferences' => [
                    [
                        'contest_housing_unit_id' => $first->id,
                        'preference_order' => 1,
                    ],
                    [
                        'contest_housing_unit_id' => $second->id,
                        'preference_order' => 2,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('housing_preferences', [
            'application_id' => $context['application']->id,
            'contest_housing_unit_id' => $first->id,
            'preference_order' => 1,
        ]);
        $this->assertDatabaseHas('housing_preferences', [
            'application_id' => $context['application']->id,
            'contest_housing_unit_id' => $second->id,
            'preference_order' => 2,
        ]);
        $this->assertSame(
            ContestHousingUnitStatus::Available,
            $first->fresh()->status,
        );
        $this->assertSame(
            HousingUnitStatus::Available,
            $first->housingUnit->fresh()->status,
        );
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'allocations',
            'action' => 'housing_preferences_updated',
        ]);
    }

    public function test_multiple_candidates_can_select_the_same_unit_without_reservation(): void
    {
        $context = $this->compatibleHousingContext();
        $unit = $this->compatibleContestHousingUnit($context);
        $other = $this->candidateApplicationForHousingContext($context);
        $service = app(HousingPreferenceService::class);
        $selection = [[
            'contest_housing_unit_id' => $unit->id,
            'preference_order' => 1,
        ]];

        $service->replace(
            $context['application'],
            $selection,
            $context['candidate'],
        );
        $service->replace(
            $other['application'],
            $selection,
            $other['candidate'],
        );

        $this->assertDatabaseCount('housing_preferences', 2);
        $this->assertSame(
            ContestHousingUnitStatus::Available,
            $unit->fresh()->status,
        );
        $this->assertSame(
            HousingUnitStatus::Available,
            $unit->housingUnit->fresh()->status,
        );
    }
}
