<?php

namespace Tests\Feature\UX\Concerns;

use App\Models\AdhesionRegistration;
use App\Models\Application;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\Household;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
use App\Models\Municipality;
use App\Models\MunicipalTeam;
use App\Models\Program;
use App\Models\PropertyInspection;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WorkTask;
use Database\Seeders\SystemAccessSeeder;

trait CreatesAnalyticsFixtures
{
    protected function seedAccess(): void
    {
        $this->seed(SystemAccessSeeder::class);
    }

    protected function analyticsUser(string $role = 'administrator'): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole($role);

        return $user;
    }

    protected function createAnalyticsFixtures(?User $assignedUser = null): void
    {
        $municipalityId = $assignedUser->municipality_id
            ?? Municipality::factory()->create()->id;
        $candidate = User::factory()->create([
            'municipality_id' => $municipalityId,
        ]);
        $registration = AdhesionRegistration::factory()->create([
            'user_id' => $candidate->id,
        ]);
        $household = Household::factory()->candidate($registration)->create([
            'municipality_id' => $municipalityId,
        ]);
        $program = Program::factory()->create([
            'municipality_id' => $municipalityId,
        ]);
        $situation = CurrentHousingSituation::factory()->create([
            'adhesion_registration_id' => $registration->id,
            'current_parish' => 'Alcanena',
            'current_housing_typology' => 'T2',
        ]);
        $applications = Application::factory()->submitted()->count(2)->create([
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'program_id' => $program->id,
            'household_id' => $household->id,
            'current_housing_situation_id' => $situation->id,
        ]);

        DocumentSubmission::factory()->create([
            'application_id' => $applications->firstOrFail()->id,
            'user_id' => $candidate->id,
            'adhesion_registration_id' => $registration->id,
            'status' => 'under_review',
        ]);
        SupportTicket::factory()->create([
            'user_id' => $candidate->id,
            'status' => 'open',
        ]);

        $housingUnit = HousingUnit::factory()->publiclyVisible()->create([
            'municipality_id' => $municipalityId,
            'parish' => 'Alcanena',
        ]);
        MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnit->id,
            'status' => 'open',
        ]);
        PropertyInspection::factory()->create([
            'housing_unit_id' => $housingUnit->id,
            'status' => 'scheduled',
        ]);

        $team = MunicipalTeam::factory()->create([
            'municipality_id' => $municipalityId,
        ]);
        $taskContext = [
            'municipal_team_id' => $team->id,
            'assigned_user_id' => $assignedUser?->id,
            'created_by' => $assignedUser?->id,
        ];

        WorkTask::factory()->create($taskContext + [
            'status' => WorkTask::STATUS_OVERDUE,
            'priority' => WorkTask::PRIORITY_HIGH,
            'due_at' => now()->subDay(),
        ]);

        WorkTask::factory()->create($taskContext + [
            'status' => WorkTask::STATUS_ASSIGNED,
            'priority' => WorkTask::PRIORITY_NORMAL,
            'due_at' => now()->addDays(2),
        ]);
    }
}
