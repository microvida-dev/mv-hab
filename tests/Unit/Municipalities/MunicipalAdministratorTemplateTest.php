<?php

namespace Tests\Unit\Municipalities;

use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Municipalities\MunicipalityOnboardingPlanner;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalAdministratorTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_template_is_exact_municipal_and_can_delegate_contest_operations(): void
    {
        $registry = app(MunicipalRoleTemplateRegistry::class);
        $administrator = $registry->resolve(MunicipalityOnboardingPlanner::TEMPLATE_KEY);
        $technician = $registry->resolve('tecnico-operacoes-concurso');

        $this->assertSame('1.0.0', $administrator['version']);
        $this->assertSame([], $administrator['entitlement_dependencies']);
        $this->assertNotContains('*', $administrator['permissions']);
        $this->assertFalse(
            collect($administrator['permissions'])
                ->contains(fn (string $permission): bool => str_contains($permission, '*')),
        );
        $this->assertContains('roles.assign', $administrator['permissions']);
        $this->assertContains('users.create', $administrator['permissions']);
        $this->assertContains('programs.create', $administrator['permissions']);
        $this->assertContains('contests.create', $administrator['permissions']);
        $this->assertNotContains('platform_operators.manage', $administrator['permissions']);
        $this->assertNotContains('municipality_features.update', $administrator['permissions']);

        foreach ($technician['permissions'] as $permission) {
            $this->assertContains($permission, $administrator['permissions']);
        }
    }
}
