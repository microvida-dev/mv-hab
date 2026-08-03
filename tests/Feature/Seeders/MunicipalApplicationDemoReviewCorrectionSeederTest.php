<?php

namespace Tests\Feature\Seeders;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Enums\ApplicationSnapshotType;
use App\Enums\ApplicationStatus;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use App\Enums\DocumentStatus;
use App\Enums\OfficialNotificationStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\ApplicationSnapshot;
use App\Models\CorrectionRequest;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\OfficialNotification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCandidateSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCatalogSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoReviewCorrectionSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoSubmissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MunicipalApplicationDemoReviewCorrectionSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MVHAB-Demo-2026!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';

        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set(
            'mvhab.municipal_application_demo.enabled',
            true,
        );
        config()->set(
            'mvhab.municipal_application_demo.reference_date',
            '2026-07-27',
        );
        config()->set(
            'mvhab.municipal_application_demo.user_password',
            self::PASSWORD,
        );
        config()->set('document-ai.enabled', true);

        Storage::fake('local');
        Queue::fake();

        CarbonImmutable::setTestNow(
            CarbonImmutable::create(
                2026,
                7,
                27,
                12,
                0,
                timezone: 'Europe/Lisbon',
            ),
        );
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_orchestrator_creates_complete_administrative_lifecycle(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $process = $this->process($application);
        $analyst = $this->analyst();

        $this->assertSame(
            AdministrativeProcessStatus::EligibilityReview,
            $process->status,
        );
        $this->assertSame($application->id, $process->application_id);
        $this->assertSame($application->user_id, $process->user_id);
        $this->assertSame($analyst->id, $process->assigned_to);
        $this->assertNotNull($process->received_at);
        $this->assertNotNull($process->assigned_at);
        $this->assertNotNull($process->preliminary_review_started_at);
        $this->assertNotNull($process->document_review_started_at);
        $this->assertNotNull($process->eligibility_review_started_at);
        $this->assertNotNull($process->current_correction_request_id);

        $history = DB::table(
            'administrative_process_status_histories',
        )
            ->where('administrative_process_id', $process->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(10, $history);
        $this->assertSame(
            [
                AdministrativeProcessStatus::Received->value,
                AdministrativeProcessStatus::Assigned->value,
                AdministrativeProcessStatus::PreliminaryReview->value,
                AdministrativeProcessStatus::DocumentReview->value,
                AdministrativeProcessStatus::EligibilityReview->value,
                AdministrativeProcessStatus::RequiresCorrection->value,
                AdministrativeProcessStatus::AwaitingCandidateResponse->value,
                AdministrativeProcessStatus::CorrectionSubmitted->value,
                AdministrativeProcessStatus::CorrectionUnderReview->value,
                AdministrativeProcessStatus::EligibilityReview->value,
            ],
            $history->pluck('to_status')->all(),
        );

        $this->assertSame(
            10,
            DB::table('audit_logs')
                ->where(
                    'auditable_type',
                    $process->getMorphClass(),
                )
                ->where('auditable_id', $process->id)
                ->where(
                    'module',
                    'administrative_processes',
                )
                ->where('action', 'status_transition')
                ->orWhere(function ($query) use ($process): void {
                    $query
                        ->where(
                            'auditable_type',
                            $process->getMorphClass(),
                        )
                        ->where('auditable_id', $process->id)
                        ->where(
                            'module',
                            'administrative_processes',
                        )
                        ->where('action', 'create');
                })
                ->count(),
        );
    }

    public function test_document_review_rejects_replaces_and_validates_one_document(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $submissions = $this->submissions($application);
        $target = $this->target($application);

        $this->assertCount(15, $submissions);
        $this->assertTrue(
            $submissions->every(
                static fn (
                    DocumentSubmission $submission,
                ): bool => $submission->status
                    === DocumentStatus::Validated,
            ),
        );

        $this->assertCount(2, $target->versions);
        $this->assertCount(4, $target->reviews);

        $versions = $target->versions
            ->sortBy('version_number')
            ->values();
        $first = $versions->get(0);
        $second = $versions->get(1);

        $this->assertInstanceOf(DocumentVersion::class, $first);
        $this->assertInstanceOf(DocumentVersion::class, $second);
        $this->assertSame(1, $first->version_number);
        $this->assertSame(2, $second->version_number);
        $this->assertSame(
            DocumentStatus::Replaced,
            $first->status_at_upload,
        );
        $this->assertSame(
            DocumentStatus::Submitted,
            $second->status_at_upload,
        );
        $this->assertNotSame($first->checksum, $second->checksum);
        $this->assertSame($second->id, $target->current_version_id);
        $this->assertSame(
            'demo-correction-document-001.pdf',
            $second->original_filename,
        );

        Storage::disk('local')->assertExists($first->storage_path);
        Storage::disk('local')->assertExists($second->storage_path);

        $this->assertSame(
            [
                DocumentStatus::UnderReview->value,
                DocumentStatus::Rejected->value,
                DocumentStatus::UnderReview->value,
                DocumentStatus::Validated->value,
            ],
            $target->reviews
                ->sortBy('id')
                ->pluck('to_status')
                ->map(
                    static fn ($status): string => $status instanceof DocumentStatus
                            ? $status->value
                            : (string) $status,
                )
                ->all(),
        );

        foreach (
            $submissions->where('id', '!=', $target->id) as $submission
        ) {
            $this->assertCount(1, $submission->versions);
            $this->assertCount(2, $submission->reviews);
            $this->assertSame(
                [
                    DocumentStatus::UnderReview->value,
                    DocumentStatus::Validated->value,
                ],
                $submission->reviews
                    ->sortBy('id')
                    ->pluck('to_status')
                    ->map(
                        static fn ($status): string => $status instanceof DocumentStatus
                                ? $status->value
                                : (string) $status,
                    )
                    ->all(),
            );
        }

        $this->assertDatabaseCount('document_ai_analyses', 0);
        Queue::assertNothingPushed();
        $this->assertTrue((bool) config('document-ai.enabled'));
    }

    public function test_application_reviews_preserve_initial_failure_and_successful_reanalysis(): void
    {
        $this->seedDemo();

        $process = $this->process($this->application());
        $reviews = ApplicationReview::query()
            ->where('administrative_process_id', $process->id)
            ->with('items')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $reviews);

        $initial = $reviews->firstWhere(
            'review_type',
            ApplicationReviewType::Documental,
        );
        $correction = $reviews->firstWhere(
            'review_type',
            ApplicationReviewType::CorrectionResponse,
        );

        $this->assertInstanceOf(ApplicationReview::class, $initial);
        $this->assertInstanceOf(ApplicationReview::class, $correction);

        $this->assertSame(
            ApplicationReviewStatus::Completed,
            $initial->status,
        );
        $this->assertSame(
            ApplicationReviewResult::RequiresCorrection,
            $initial->result,
        );
        $this->assertSame(
            MunicipalApplicationDemoReviewCorrectionSeeder::INITIAL_REVIEW_SUMMARY,
            $initial->summary,
        );
        $this->assertCount(15, $initial->items);
        $this->assertSame(
            1,
            $initial->items
                ->where('requires_correction', true)
                ->count(),
        );

        $this->assertSame(
            ApplicationReviewStatus::Completed,
            $correction->status,
        );
        $this->assertSame(
            ApplicationReviewResult::Passed,
            $correction->result,
        );
        $this->assertSame(
            MunicipalApplicationDemoReviewCorrectionSeeder::CORRECTION_REVIEW_SUMMARY,
            $correction->summary,
        );
        $this->assertCount(1, $correction->items);
        $this->assertFalse(
            $correction->items->sole()->requires_correction,
        );
    }

    public function test_correction_request_response_and_notifications_are_complete(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $process = $this->process($application);
        $request = CorrectionRequest::query()
            ->where('administrative_process_id', $process->id)
            ->where(
                'subject',
                MunicipalApplicationDemoReviewCorrectionSeeder::CORRECTION_SUBJECT,
            )
            ->with(['items.responses'])
            ->sole();

        $this->assertSame(
            CorrectionRequestStatus::Resolved,
            $request->status,
        );
        $this->assertTrue($request->candidate_visible);
        $this->assertNotNull($request->issued_at);
        $this->assertNotNull($request->responded_at);
        $this->assertNotNull($request->closed_at);
        $this->assertSame(
            CarbonImmutable::create(
                2026,
                8,
                6,
                17,
                0,
                timezone: 'Europe/Lisbon',
            )->toIso8601String(),
            $request->response_deadline_at?->toIso8601String(),
        );

        $item = $request->items->sole();
        $response = $item->responses->sole();

        $this->assertTrue($item->is_required);
        $this->assertSame(
            CorrectionRequestItemStatus::Accepted,
            $item->status,
        );
        $this->assertSame(
            CorrectionResponseStatus::Accepted,
            $response->status,
        );
        $this->assertSame(
            CorrectionResponseReviewResult::Accepted,
            $response->review_result,
        );
        $this->assertNotNull($response->submitted_at);
        $this->assertNotNull($response->reviewed_at);
        $this->assertSame(
            $this->target($application)->id,
            $response->document_submission_id,
        );

        $notifications = OfficialNotification::query()
            ->where('application_id', $application->id)
            ->where(
                'notifiable_type',
                $request->getMorphClass(),
            )
            ->where('notifiable_id', $request->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $notifications);
        $this->assertSame(
            [
                MunicipalApplicationDemoReviewCorrectionSeeder::ISSUE_NOTIFICATION_SUBJECT,
                MunicipalApplicationDemoReviewCorrectionSeeder::ACCEPTED_NOTIFICATION_SUBJECT,
            ],
            $notifications->pluck('subject')->all(),
        );
        $this->assertTrue(
            $notifications->first()->requires_acknowledgement,
        );
        $this->assertFalse(
            $notifications->last()->requires_acknowledgement,
        );

        foreach ($notifications as $notification) {
            $this->assertSame(
                OfficialNotificationStatus::Published,
                $notification->status,
            );
            $this->assertNotNull($notification->communication_log_id);
            $this->assertSame(
                route(
                    'candidate.correction-requests.show',
                    $request,
                    false,
                ),
                $notification->action_url,
            );
        }

        $this->assertDatabaseCount('communication_logs', 2);
        $this->assertDatabaseCount('communication_deliveries', 4);
        $this->assertSame(
            2,
            DB::table('communication_deliveries')
                ->where(
                    'channel',
                    CommunicationChannel::InApp->value,
                )
                ->where(
                    'status',
                    CommunicationDeliveryStatus::Delivered->value,
                )
                ->count(),
        );
        $this->assertSame(
            2,
            DB::table('communication_deliveries')
                ->where(
                    'channel',
                    CommunicationChannel::Email->value,
                )
                ->where(
                    'status',
                    CommunicationDeliveryStatus::Simulated->value,
                )
                ->count(),
        );
        Queue::assertNothingPushed();
    }

    public function test_formal_submission_snapshots_remain_immutable_after_correction(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $target = $this->target($application);
        $versions = $target->versions
            ->sortBy('version_number')
            ->values();
        $original = $versions->get(0);
        $corrected = $versions->get(1);

        $snapshot = ApplicationSnapshot::query()
            ->where('application_id', $application->id)
            ->where(
                'snapshot_type',
                ApplicationSnapshotType::Documents->value,
            )
            ->sole();

        $rows = collect($snapshot->data);
        $row = $rows->firstWhere(
            'document_submission_id',
            $target->id,
        );

        $this->assertIsArray($row);
        $this->assertSame(1, $row['version_number'] ?? null);
        $this->assertSame(
            $original?->checksum,
            $row['checksum'] ?? null,
        );
        $this->assertNotSame(
            $corrected?->checksum,
            $row['checksum'] ?? null,
        );

        $applicationDocument = DB::table('application_documents')
            ->where('application_id', $application->id)
            ->where('document_submission_id', $target->id)
            ->sole();

        $this->assertSame(
            DocumentStatus::Submitted->value,
            $applicationDocument->status_at_submission,
        );
        $this->assertSame(
            ApplicationStatus::Submitted,
            $application->status,
        );
        $this->assertDatabaseCount('application_snapshots', 8);
    }

    public function test_complete_orchestrator_is_idempotent_after_correction(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $first = $this->stableState($application);

        $this->seedDemo();

        $application = $this->application();
        $second = $this->stableState($application);

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('document_ai_analyses', 0);
        Queue::assertNothingPushed();
    }

    private function seedDemo(): void
    {
        $this->assertTrue(
            class_exists(
                MunicipalApplicationDemoReviewCorrectionSeeder::class,
            ),
            'Falta implementar o seeder combinado 51E.',
        );

        $this->seed(
            MunicipalApplicationDemoAccessSeeder::class,
        );
        $this->seed(
            MunicipalApplicationDemoCatalogSeeder::class,
        );
        $this->seed(
            MunicipalApplicationDemoCandidateSeeder::class,
        );
        $this->seed(
            MunicipalApplicationDemoSubmissionSeeder::class,
        );
        $this->seed(
            MunicipalApplicationDemoReviewCorrectionSeeder::class,
        );
    }

    private function application(): Application
    {
        return Application::query()
            ->whereHas(
                'user',
                static fn ($query) => $query->where(
                    'email',
                    MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
                ),
            )
            ->whereHas(
                'contest',
                static fn ($query) => $query->where(
                    'code',
                    MunicipalApplicationDemoCatalogSeeder::CONTEST_CODE,
                ),
            )
            ->with('snapshots')
            ->sole();
    }

    private function analyst()
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EMAIL,
            )
            ->sole();
    }

    private function process(
        Application $application,
    ): AdministrativeProcess {
        return AdministrativeProcess::query()
            ->where('application_id', $application->id)
            ->sole();
    }

    /**
     * @return Collection<int, DocumentSubmission>
     */
    private function submissions(
        Application $application,
    ): Collection {
        return DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->with(['versions', 'reviews'])
            ->orderBy('id')
            ->get();
    }

    private function target(
        Application $application,
    ): DocumentSubmission {
        return DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->where('requirement_instance', 3)
            ->whereHas(
                'documentType',
                static fn ($query) => $query->where(
                    'code',
                    'recibos_vencimento',
                ),
            )
            ->orderBy('income_record_id')
            ->with(['versions', 'reviews'])
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function stableState(
        Application $application,
    ): array {
        $process = $this->process($application);
        $request = CorrectionRequest::query()
            ->where('administrative_process_id', $process->id)
            ->with(['items.responses'])
            ->sole();
        $submissions = $this->submissions($application);

        return [
            'application' => [
                'id' => $application->id,
                'status' => $application->status->value,
                'application_number' => $application->application_number,
            ],
            'process' => [
                'id' => $process->id,
                'number' => $process->process_number,
                'status' => $process->status->value,
                'current_correction_request_id' => $process->current_correction_request_id,
            ],
            'process_history' => DB::table(
                'administrative_process_status_histories',
            )
                ->where('administrative_process_id', $process->id)
                ->orderBy('id')
                ->get()
                ->map(
                    static fn ($row): array => [
                        'id' => $row->id,
                        'from' => $row->from_status,
                        'to' => $row->to_status,
                        'changed_by' => $row->changed_by,
                    ],
                )
                ->all(),
            'reviews' => ApplicationReview::query()
                ->where('administrative_process_id', $process->id)
                ->with('items')
                ->orderBy('id')
                ->get()
                ->map(
                    static fn (
                        ApplicationReview $review,
                    ): array => [
                        'id' => $review->id,
                        'type' => $review->review_type->value,
                        'status' => $review->status->value,
                        'result' => $review->result?->value,
                        'items' => $review->items
                            ->pluck('id')
                            ->all(),
                    ],
                )
                ->all(),
            'request' => [
                'id' => $request->id,
                'number' => $request->request_number,
                'status' => $request->status->value,
                'items' => $request->items
                    ->map(
                        static fn ($item): array => [
                            'id' => $item->id,
                            'status' => $item->status->value,
                            'responses' => $item->responses
                                ->map(
                                    static fn ($response): array => [
                                        'id' => $response->id,
                                        'status' => $response->status->value,
                                        'document_submission_id' => $response
                                            ->document_submission_id,
                                    ],
                                )
                                ->all(),
                        ],
                    )
                    ->all(),
            ],
            'documents' => $submissions
                ->map(
                    static fn (
                        DocumentSubmission $submission,
                    ): array => [
                        'id' => $submission->id,
                        'status' => $submission->status->value,
                        'current_version_id' => $submission->current_version_id,
                        'versions' => $submission->versions
                            ->sortBy('version_number')
                            ->map(
                                static fn (
                                    DocumentVersion $version,
                                ): array => [
                                    'id' => $version->id,
                                    'number' => $version->version_number,
                                    'checksum' => $version->checksum,
                                    'path' => $version->storage_path,
                                ],
                            )
                            ->values()
                            ->all(),
                        'reviews' => $submission->reviews
                            ->sortBy('id')
                            ->pluck('id')
                            ->values()
                            ->all(),
                    ],
                )
                ->all(),
            'notifications' => OfficialNotification::query()
                ->where('application_id', $application->id)
                ->orderBy('id')
                ->get()
                ->map(
                    static fn (
                        OfficialNotification $notification,
                    ): array => [
                        'id' => $notification->id,
                        'number' => $notification->notification_number,
                        'subject' => $notification->subject,
                        'status' => $notification->status->value,
                        'communication_log_id' => $notification->communication_log_id,
                    ],
                )
                ->all(),
            'counts' => [
                'application_snapshots' => DB::table('application_snapshots')->count(),
                'application_documents' => DB::table('application_documents')->count(),
                'document_versions' => DB::table('document_versions')->count(),
                'document_reviews' => DB::table('document_reviews')->count(),
                'audit_logs' => DB::table('audit_logs')->count(),
                'communication_logs' => DB::table('communication_logs')->count(),
                'communication_deliveries' => DB::table('communication_deliveries')->count(),
            ],
            'files' => DocumentVersion::query()
                ->whereIn(
                    'document_submission_id',
                    $submissions->modelKeys(),
                )
                ->orderBy('id')
                ->get()
                ->mapWithKeys(
                    static fn (
                        DocumentVersion $version,
                    ): array => [
                        $version->storage_path => hash(
                            'sha256',
                            Storage::disk(
                                $version->storage_disk,
                            )->get($version->storage_path),
                        ),
                    ],
                )
                ->all(),
        ];
    }
}
