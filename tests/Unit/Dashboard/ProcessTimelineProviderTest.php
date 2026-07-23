<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\ApplicationStatus;
use App\Enums\ComplaintDecisionStatus;
use App\Enums\ComplaintStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\ApplicationTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\ComplaintTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\CorrectionRequestTimelineProvider;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProcessTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_application_provider_uses_current_workflow_statuses(): void
    {
        Application::factory()->create([
            'application_number' => 'CAND-2026-TIMELINE',
            'status' => ApplicationStatus::UnderReview,
            'submitted_at' => now(),
        ]);

        Application::factory()->create([
            'status' => ApplicationStatus::Draft,
            'submitted_at' => null,
        ]);

        $events = (new ApplicationTimelineProvider)->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::ApplicationSubmitted, $events[0]->type);
        $this->assertSame(ApplicationStatus::UnderReview->value, $events[0]->metadata['status']);
    }

    public function test_complaint_provider_uses_current_statuses_for_open_items_and_decisions(): void
    {
        Complaint::factory()->create([
            'status' => ComplaintStatus::UnderReview,
            'submitted_at' => now()->subDay(),
        ]);

        Complaint::factory()->create([
            'status' => ComplaintStatus::AwaitingCandidateResponse,
            'additional_information_deadline_at' => now()->addDays(2),
        ]);

        ComplaintDecision::factory()->create([
            'status' => ComplaintDecisionStatus::Proposed,
            'proposed_at' => now(),
        ]);

        $events = (new ComplaintTimelineProvider)->forUser($this->authorizedUser());
        $types = collect($events)->pluck('type')->all();

        $this->assertCount(3, $events);
        $this->assertContains(TimelineType::Complaint, $types);
        $this->assertContains(TimelineType::ComplaintAdditionalInformation, $types);
        $this->assertContains(TimelineType::ComplaintDecision, $types);
    }

    public function test_correction_provider_exposes_open_request_and_submitted_response(): void
    {
        CorrectionRequest::factory()->create([
            'status' => CorrectionRequestStatus::Open,
            'response_deadline_at' => now()->addDays(3),
        ]);

        CorrectionResponse::factory()->create();

        $events = (new CorrectionRequestTimelineProvider)->forUser($this->authorizedUser());
        $types = collect($events)->pluck('type')->all();

        $this->assertCount(2, $events);
        $this->assertContains(TimelineType::CorrectionRequest, $types);
        $this->assertContains(TimelineType::CorrectionResponse, $types);

        $responseEvent = collect($events)->firstWhere('type', TimelineType::CorrectionResponse);
        $this->assertInstanceOf(TimelineEvent::class, $responseEvent);
        $this->assertArrayHasKey('request_number', $responseEvent->metadata);
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');

        return $user;
    }
}
