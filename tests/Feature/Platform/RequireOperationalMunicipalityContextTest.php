<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Http\Middleware\RequireOperationalMunicipalityContext;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Services\Platform\PlatformMunicipalContextService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequireOperationalMunicipalityContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);

        Route::middleware(['web', 'municipality.context'])
            ->get(
                '/__tests/operational-municipality-context',
                static function (Request $request): array {
                    return [
                        'municipality_id' => $request->attributes->get(
                            RequireOperationalMunicipalityContext::ATTRIBUTE_ID,
                        ),
                    ];
                },
            );
    }

    public function test_platform_operator_without_context_is_refused(): void
    {
        $user = $this->platformAdministrator();

        $this->actingAs($user)
            ->get('/__tests/operational-municipality-context')
            ->assertForbidden();
    }

    public function test_platform_operator_with_valid_context_passes(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformAdministrator();

        $this->actingAs($user)
            ->withSession([
                PlatformMunicipalContextService::SESSION_KEY => $municipality->id,
            ])
            ->get('/__tests/operational-municipality-context')
            ->assertOk()
            ->assertJson([
                'municipality_id' => $municipality->id,
            ]);
    }

    public function test_municipal_user_passes_without_platform_session_context(): void
    {
        $municipality = Municipality::factory()->create();
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole('municipal_technician');

        $this->actingAs($user)
            ->get('/__tests/operational-municipality-context')
            ->assertOk()
            ->assertJson([
                'municipality_id' => $municipality->id,
            ]);
    }

    public function test_candidate_is_refused(): void
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('candidate');

        $this->actingAs($user)
            ->get('/__tests/operational-municipality-context')
            ->assertForbidden();
    }

    private function platformAdministrator(): User
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');
        PlatformOperatorAssignment::factory()->for($user)->create();

        return $user;
    }
}
