<?php

namespace Tests\Feature\Seeders;

use App\Models\MunicipalTeam;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskSlaPolicy;
use Database\Seeders\MunicipalEndToEndWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalEndToEndWorkflowSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_workflow_seeder_creates_idempotent_personas_teams_and_sla(): void
    {
        $this->seed(MunicipalEndToEndWorkflowSeeder::class);
        $this->seed(MunicipalEndToEndWorkflowSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'e2e.tecnico@example.test')->count());
        $this->assertSame(1, User::query()->where('email', 'e2e.juri@example.test')->count());
        $this->assertSame(1, User::query()->where('email', 'e2e.candidato@example.test')->count());

        $technician = User::query()->where('email', 'e2e.tecnico@example.test')->firstOrFail();
        $jury = User::query()->where('email', 'e2e.juri@example.test')->firstOrFail();
        $candidate = User::query()->where('email', 'e2e.candidato@example.test')->firstOrFail();

        $this->assertTrue($technician->hasRole('municipal_technician'));
        $this->assertTrue($jury->hasRole('jury'));
        $this->assertTrue($candidate->hasRole('candidate'));

        $this->assertTrue($technician->municipalTeams()->where('municipal_teams.name', 'Gabinete Técnico')->exists());
        $this->assertTrue($jury->municipalTeams()->where('municipal_teams.name', 'Gabinete Jurídico')->exists());

        $technicalTeam = MunicipalTeam::query()->where('name', 'Gabinete Técnico')->firstOrFail();
        $this->assertSame($technician->id, $technicalTeam->manager_user_id);

        $this->assertSame(1, WorkTaskSlaPolicy::query()->where('type', WorkTask::TYPE_DOCUMENT_REVIEW)->count());
        $this->assertSame(5, WorkTaskSlaPolicy::query()->where('type', WorkTask::TYPE_DOCUMENT_REVIEW)->value('business_days'));
    }
}
