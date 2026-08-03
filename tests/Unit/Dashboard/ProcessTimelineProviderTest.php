<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\ApplicationStatus;
use App\Enums\ComplaintDecisionStatus;
use App\Enums\ComplaintStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\Complaint;
use App\Models\ComplaintDecision;
use App\Models\User;
use App\Services\Dashboard\Timeline\Providers\ApplicationTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\ComplaintTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\CorrectionRequestTimelineProvider;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesPublishedCorrectionRequests;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

final class ProcessTimelineProviderTest extends TestCase
{
    use CreatesPublishedCorrectionRequests;
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        Queue::fake();
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

        $events = (new ApplicationTimelineProvider)
            ->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(
            TimelineType::ApplicationSubmitted,
            $events[0]->type,
        );
        $this->assertSame(
            ApplicationStatus::UnderReview->value,
            $events[0]->metadata['status'],
        );
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

        $events = (new ComplaintTimelineProvider)
            ->forUser($this->authorizedUser());
        $types = collect($events)->pluck('type')->all();

        $this->assertCount(3, $events);
        $this->assertContains(
            TimelineType::Complaint,
            $types,
        );
        $this->assertContains(
            TimelineType::ComplaintAdditionalInformation,
            $types,
        );
        $this->assertContains(
            TimelineType::ComplaintDecision,
            $types,
        );
    }

    public function test_correction_provider_exposes_one_aggregate_event_per_request(): void
    {
        $municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $user = $this->assignMunicipality(
            $this->authorizedUser(),
            $municipality,
        );

        $open = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $user,
            status: CorrectionRequestStatus::Open,
            completedItems: 1,
            totalItems: 2,
            deadline: now()->addDays(3),
        );

        $submitted = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $user,
            status: CorrectionRequestStatus::Submitted,
            completedItems: 2,
            totalItems: 2,
            deadline: now()->addDays(2),
        );

        $events = app(CorrectionRequestTimelineProvider::class)
            ->forUser($user);
        $types = collect($events)->pluck('type')->all();

        $this->assertCount(2, $events);
        $this->assertContains(
            TimelineType::CorrectionRequest,
            $types,
        );
        $this->assertContains(
            TimelineType::CorrectionResponse,
            $types,
        );

        $openEvent = collect($events)->firstWhere(
            'id',
            'correction-request-'.$open->id,
        );
        $this->assertInstanceOf(
            TimelineEvent::class,
            $openEvent,
        );
        $this->assertSame(
            50,
            $openEvent->metadata['progress_percentage'],
        );

        $submittedEvents = collect($events)->where(
            'id',
            'correction-request-'.$submitted->id,
        );
        $this->assertCount(1, $submittedEvents);
    }

    private function authorizedUser(): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        return $user;
    }
}
