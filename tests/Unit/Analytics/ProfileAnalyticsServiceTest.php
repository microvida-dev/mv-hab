<?php

namespace Tests\Unit\Analytics;

use App\Models\User;
use App\Services\Analytics\ProfileAnalyticsService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_service_resolves_municipal_profile_label(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('financial_manager');

        $profile = app(ProfileAnalyticsService::class)->resolve($user);

        $this->assertSame('Gestor Financeiro', $profile['label']);
        $this->assertTrue($profile['financial']);
    }
}
