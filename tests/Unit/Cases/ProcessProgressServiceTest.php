<?php

namespace Tests\Unit\Cases;

use App\Models\Application;
use App\Models\EligibilityCheck;
use App\Services\Cases\ProcessProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligibility_step_becomes_current_when_latest_check_exists(): void
    {
        $application = Application::factory()->submitted()->create();
        EligibilityCheck::factory()->create([
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
        ]);

        $steps = collect(app(ProcessProgressService::class)->forApplication($application))->keyBy('key');

        $this->assertSame('done', $steps->get('documents')['status']);
        $this->assertSame('current', $steps->get('eligibility')['status']);
        $this->assertSame('pending', $steps->get('scoring')['status']);
    }
}
