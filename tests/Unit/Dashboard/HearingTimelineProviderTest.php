<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\HearingStatus;
use App\Enums\HearingSubmissionStatus;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\HearingTimelineProvider;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HearingTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_provider_exposes_only_active_hearings(): void
    {
        Hearing::factory()->create([
            'status' => HearingStatus::Open,
            'deadline_at' => now()->addDays(2),
        ]);

        Hearing::factory()->create([
            'status' => HearingStatus::Closed,
            'deadline_at' => now()->addDay(),
        ]);

        $events = (new HearingTimelineProvider)->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::Hearing, $events[0]->type);
        $this->assertSame(HearingStatus::Open->value, $events[0]->metadata['status']);
    }

    public function test_submission_event_uses_related_hearing_number(): void
    {
        $hearing = Hearing::factory()->create([
            'hearing_number' => 'AUD-2026-000123',
            'status' => HearingStatus::Closed,
        ]);

        HearingSubmission::factory()->create([
            'hearing_id' => $hearing,
            'status' => HearingSubmissionStatus::Submitted,
        ]);

        $events = (new HearingTimelineProvider)->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::HearingSubmission, $events[0]->type);
        $this->assertSame('AUD-2026-000123', $events[0]->metadata['hearing_number']);
        $this->assertSame('AUD-2026-000123 · aguarda análise', $events[0]->description);
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');

        return $user;
    }
}
