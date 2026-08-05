<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Services\Dashboard\DashboardAuthorizationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdministratorProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_global_operator_is_presented_as_platform_administration(): void
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');
        PlatformOperatorAssignment::factory()->for($user)->create();

        $authorization = app(DashboardAuthorizationService::class);

        $this->assertSame(
            'platform_administrator',
            $authorization->primaryProfile($user),
        );
        $this->assertSame(
            'Administração da plataforma',
            $authorization->profileLabel($user),
        );
    }

    public function test_municipal_administrator_keeps_municipal_label(): void
    {
        $municipality = Municipality::factory()->create();
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        $authorization = app(DashboardAuthorizationService::class);

        $this->assertSame(
            'administrator',
            $authorization->primaryProfile($user),
        );
        $this->assertSame(
            'Administração municipal',
            $authorization->profileLabel($user),
        );
    }

    public function test_administrator_role_without_structural_scope_is_fail_closed(): void
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        $authorization = app(DashboardAuthorizationService::class);

        $this->assertSame(
            'unclassified',
            $authorization->primaryProfile($user),
        );
        $this->assertSame(
            'Acesso não classificado',
            $authorization->profileLabel($user),
        );
    }
}
