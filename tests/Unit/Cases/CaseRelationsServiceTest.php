<?php

namespace Tests\Unit\Cases;

use App\Models\Application;
use App\Models\Contract;
use App\Models\User;
use App\Services\Cases\CaseRelationsService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseRelationsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_relations_are_filtered_and_return_authorized_items(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');
        $application = Application::factory()->submitted()->create();
        $contract = Contract::factory()->create(['application_id' => $application->id]);

        $relations = app(CaseRelationsService::class)->forCase($user, 'contract', $contract);

        $this->assertTrue(collect($relations)->contains(fn ($relation): bool => str_contains($relation->label, 'Candidatura')));
    }
}
