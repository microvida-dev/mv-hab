<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Exceptions\AccessDeniedException;
use App\Http\Controllers\DashboardController;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Services\Dashboard\DashboardAuthorizationService;
use App\Services\Dashboard\MunicipalOperationsDashboardService;
use App\Services\Dashboard\PlatformOperationsDashboardService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class PlatformAdministratorDashboardIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_platform_dashboard_payload_contains_no_municipal_operations(): void
    {
        $user = $this->platformAdministrator();

        $payload = app(PlatformOperationsDashboardService::class)
            ->forUser($user);

        $this->assertSame(
            'platform_administrator',
            data_get($payload, 'dashboard.adaptive_dashboard.profile'),
        );
        $this->assertSame(
            'Visão global da plataforma',
            data_get($payload, 'dashboard.adaptive_dashboard.headline'),
        );
        $this->assertSame([], $payload['workspaces']);
        $this->assertSame([], $payload['productivity']);
        $this->assertSame([], $payload['todayOperations']);
        $this->assertSame([], $payload['operationsTimeline']);
        $this->assertSame([], $payload['correctionOperations']);
        $this->assertSame(
            [],
            data_get($payload, 'dashboard.metrics'),
        );
    }

    public function test_controller_never_calls_municipal_dashboard_without_context(): void
    {
        $user = $this->platformAdministrator();
        $request = Request::create('/dashboard');
        $request->setUserResolver(
            static fn (): User => $user,
        );

        $municipal = Mockery::mock(
            MunicipalOperationsDashboardService::class,
        );
        $municipal->shouldNotReceive('forUser');

        $platform = app(
            PlatformOperationsDashboardService::class,
        );

        $response = app(DashboardController::class)(
            $request,
            app(DashboardAuthorizationService::class),
            $municipal,
            $platform,
        );

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('dashboard-platform', $response->name());
    }

    public function test_unclassified_administrator_is_refused_before_municipal_dashboard(): void
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_platform_dashboard_view_contains_no_municipal_search_or_queue(): void
    {
        $user = $this->platformAdministrator();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewIs('dashboard-platform')
            ->assertSeeText('Visão global da plataforma')
            ->assertDontSeeText('Caixa de Entrada Municipal')
            ->assertDontSee('dashboard-sidebar-search', false);
    }

    public function test_platform_dashboard_service_refuses_municipal_actor(): void
    {
        $municipality = Municipality::factory()->create();
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        $this->expectException(AccessDeniedException::class);

        app(PlatformOperationsDashboardService::class)
            ->forUser($user);
    }

    private function platformAdministrator(): User
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');
        PlatformOperatorAssignment::factory()->for($user)->create();

        return $user->refresh();
    }
}
