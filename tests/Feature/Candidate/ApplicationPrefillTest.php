<?php

namespace Tests\Feature\Candidate;

use App\Enums\ApplicationPrefillStatus;
use App\Models\Application;
use App\Models\ApplicationPrefill;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationPrefillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_confirms_and_applies_own_prefill_to_editable_draft(): void
    {
        $candidate = $this->userWithRole('candidate');
        $application = Application::factory()->create(['user_id' => $candidate->id, 'candidate_notes' => null]);
        $prefill = ApplicationPrefill::factory()->create([
            'user_id' => $candidate->id,
            'application_id' => $application->id,
        ]);

        $this->actingAs($candidate)
            ->post(route('candidate.application-prefills.confirm', $prefill), [
                'confirm_data_reviewed' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(ApplicationPrefillStatus::Confirmed, $prefill->fresh()->status);

        $this->actingAs($candidate)
            ->post(route('candidate.application-prefills.apply', $prefill), [
                'confirm_apply_to_draft' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(ApplicationPrefillStatus::Applied, $prefill->fresh()->status);
        $this->assertStringContainsString('simulação indicativa', (string) $application->fresh()->candidate_notes);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
