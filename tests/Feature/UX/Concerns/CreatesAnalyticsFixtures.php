<?php

namespace Tests\Feature\UX\Concerns;

use App\Models\Application;
use App\Models\CurrentHousingSituation;
use App\Models\DocumentSubmission;
use App\Models\HousingUnit;
use App\Models\MaintenanceRequest;
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
        $situation = CurrentHousingSituation::factory()->create([
            'current_parish' => 'Alcanena',
            'current_housing_typology' => 'T2',
        ]);

        Application::factory()->submitted()->count(2)->create([
            'current_housing_situation_id' => $situation->id,
        ]);

        DocumentSubmission::factory()->create(['status' => 'under_review']);
        SupportTicket::factory()->create(['status' => 'open']);
        HousingUnit::factory()->publiclyVisible()->create(['parish' => 'Alcanena']);
        MaintenanceRequest::factory()->create(['status' => 'open']);
        PropertyInspection::factory()->create(['status' => 'scheduled']);

        WorkTask::factory()->create([
            'assigned_user_id' => $assignedUser?->id,
            'status' => WorkTask::STATUS_OVERDUE,
            'priority' => WorkTask::PRIORITY_HIGH,
            'due_at' => now()->subDay(),
        ]);

        WorkTask::factory()->create([
            'assigned_user_id' => $assignedUser?->id,
            'status' => WorkTask::STATUS_ASSIGNED,
            'priority' => WorkTask::PRIORITY_NORMAL,
            'due_at' => now()->addDays(2),
        ]);
    }
}
