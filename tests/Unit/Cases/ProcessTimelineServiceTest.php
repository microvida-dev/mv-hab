<?php

namespace Tests\Unit\Cases;

use App\Models\Application;
use App\Models\ProcessTimelineEvent;
use App\Models\User;
use App\Services\Cases\ProcessTimelineService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_timeline_is_ordered_and_sanitized(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('municipal_technician');
        $application = Application::factory()->submitted()->create(['submitted_at' => now()->subDay()]);

        ProcessTimelineEvent::factory()->create([
            'application_id' => $application->id,
            'title' => 'Evento posterior',
            'description' => 'storage/app/private/documento.pdf 123456789',
            'occurred_at' => now(),
        ]);

        $timeline = app(ProcessTimelineService::class)->forApplication($user, $application);

        $this->assertSame('application_submitted', $timeline->first()['type']);
        $this->assertStringNotContainsString('storage/app/private', (string) $timeline->last()['description']);
        $this->assertStringNotContainsString('123456789', (string) $timeline->last()['description']);
    }
}
