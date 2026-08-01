<?php

namespace Tests\Feature;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\CorrectionRequestStatus;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\FeatureKey;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Administrative\CorrectionProgressMetricsService;
use App\Services\Agenda\AgendaService;
use App\Services\Dashboard\MunicipalOperationsDashboardService;
use App\Services\Dashboard\Timeline\Providers\CorrectionRequestTimelineProvider;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesPublishedCorrectionRequests;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53ECorrectionOperationsTest extends TestCase
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

    public function test_municipal_metrics_are_scoped_and_do_not_expose_document_content(): void
    {
        $municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $foreignMunicipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $operator = $this->operator($municipality);
        $foreignOperator = $this->operator(
            $foreignMunicipality,
        );

        $request = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $operator,
            status: CorrectionRequestStatus::PartiallyCompleted,
            completedItems: 1,
            totalItems: 2,
            deadline: now()->addHours(2),
        );

        $foreign = $this->createPublishedCorrectionRequest(
            municipality: $foreignMunicipality,
            operator: $foreignOperator,
            status: CorrectionRequestStatus::Expired,
            completedItems: 0,
            totalItems: 3,
            deadline: now()->subHour(),
        );

        $metrics = app(
            CorrectionProgressMetricsService::class,
        )->municipalDashboard($operator);

        $this->assertTrue($metrics['available']);
        $this->assertSame(
            1,
            $metrics['summary']['total_requests'],
        );
        $this->assertSame(
            1,
            $metrics['summary']['active_requests'],
        );
        $this->assertSame(
            1,
            $metrics['summary']['due_soon_requests'],
        );
        $this->assertSame(
            2,
            $metrics['summary']['total_items'],
        );
        $this->assertSame(
            1,
            $metrics['summary']['completed_items'],
        );
        $this->assertSame(
            50,
            $metrics['summary']['percentage'],
        );
        $this->assertSame(
            $request->request_number,
            $metrics['urgent'][0]['request_number'],
        );

        $serialized = json_encode(
            $metrics,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'Documento confidencial',
            $serialized,
        );
        $this->assertStringNotContainsString(
            $foreign->request_number,
            $serialized,
        );
    }

    public function test_timeline_and_agenda_use_scoped_request_level_events(): void
    {
        $municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $foreignMunicipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $operator = $this->operator($municipality);
        $foreignOperator = $this->operator(
            $foreignMunicipality,
        );

        $open = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $operator,
            status: CorrectionRequestStatus::Open,
            completedItems: 1,
            totalItems: 2,
            deadline: now()->addHours(2),
        );

        $submitted = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $operator,
            status: CorrectionRequestStatus::Submitted,
            completedItems: 2,
            totalItems: 2,
            deadline: now()->addHours(4),
        );

        $foreign = $this->createPublishedCorrectionRequest(
            municipality: $foreignMunicipality,
            operator: $foreignOperator,
            status: CorrectionRequestStatus::Open,
            completedItems: 0,
            totalItems: 1,
            deadline: now()->addHour(),
        );

        $events = collect(
            app(CorrectionRequestTimelineProvider::class)
                ->forUser($operator),
        );

        $this->assertCount(2, $events);
        $this->assertNotNull(
            $events->firstWhere(
                'id',
                'correction-request-'.$open->id,
            ),
        );
        $this->assertNotNull(
            $events->firstWhere(
                'id',
                'correction-request-'.$submitted->id,
            ),
        );
        $this->assertNull(
            $events->firstWhere(
                'id',
                'correction-request-'.$foreign->id,
            ),
        );

        $submittedEvent = $events->firstWhere(
            'id',
            'correction-request-'.$submitted->id,
        );

        $this->assertInstanceOf(
            TimelineEvent::class,
            $submittedEvent,
        );
        $this->assertSame(
            TimelineType::CorrectionResponse,
            $submittedEvent->type,
        );
        $this->assertSame(
            100,
            $submittedEvent->metadata[
                'progress_percentage'
            ],
        );
        $this->assertSame(
            route(
                'backoffice.correction-requests.show',
                $submitted,
            ),
            $submittedEvent->route,
        );
        $this->assertArrayNotHasKey(
            'document_submission_id',
            $submittedEvent->metadata,
        );

        $agenda = app(AgendaService::class)->today($operator);
        $agendaEvents = $agenda['events'] ?? [];
        $this->assertIsArray($agendaEvents);

        /** @var list<array<string, mixed>> $agendaEvents */
        $agendaIds = array_column($agendaEvents, 'id');

        $this->assertContains(
            'correction-request-'.$open->id,
            $agendaIds,
        );
        $this->assertContains(
            'correction-request-'.$submitted->id,
            $agendaIds,
        );
        $this->assertNotContains(
            'correction-request-'.$foreign->id,
            $agendaIds,
        );
    }

    public function test_dashboard_payload_contains_authorized_correction_summary(): void
    {
        $municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $operator = $this->operator($municipality);

        $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $operator,
            status: CorrectionRequestStatus::Open,
            completedItems: 2,
            totalItems: 3,
            deadline: now()->addDay(),
        );

        $payload = app(
            MunicipalOperationsDashboardService::class,
        )->forUser($operator);

        $this->assertArrayHasKey(
            'correctionOperations',
            $payload,
        );

        $correctionOperations =
            $payload['correctionOperations'];
        $this->assertIsArray($correctionOperations);
        $this->assertTrue(
            $correctionOperations['available'],
        );

        $summary = $correctionOperations['summary'];
        $this->assertIsArray($summary);
        $this->assertSame(
            1,
            $summary['total_requests'],
        );
        $this->assertSame(
            67,
            $summary['percentage'],
        );
    }

    private function operator(
        Municipality $municipality,
    ): User {
        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        return $this->assignMunicipality(
            $user,
            $municipality,
        );
    }
}
