<?php

namespace Tests\Feature\Seeders;

use App\Enums\ApplicationReportStatus;
use App\Enums\DocumentDossierItemStatus;
use App\Enums\DocumentDossierStatus;
use App\Enums\ReportFormat;
use App\Enums\VisitSlotStatus;
use App\Enums\VisitStatus;
use App\Models\Application;
use App\Models\ApplicationReport;
use App\Models\DocumentDossier;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\HousingVisit;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Models\WorkTask;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoCatalogSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoVisitsExportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MunicipalApplicationDemoVisitsExportSeederTest extends TestCase
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

    public function test_orchestrator_creates_visit_availability_and_four_slots(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $manager = $this->visitManager();
        $availability = VisitAvailability::query()
            ->where('contest_id', $application->contest_id)
            ->where('staff_user_id', $manager->id)
            ->with('slots')
            ->sole();

        $this->assertSame(
            MunicipalApplicationDemoVisitsExportSeeder::AVAILABILITY_TITLE,
            $availability->title,
        );
        $this->assertSame(
            $manager->municipality_id,
            $availability->municipality_id,
        );
        $this->assertSame(
            $application->contest_id,
            $availability->contest_id,
        );
        $this->assertSame(
            $this->housingUnitId($application),
            $availability->housing_unit_id,
        );
        $this->assertSame(30, $availability->slot_duration_minutes);
        $this->assertSame(1, $availability->capacity_per_slot);
        $this->assertSame(0, $availability->buffer_minutes);
        $this->assertSame('Europe/Lisbon', $availability->timezone);
        $this->assertTrue($availability->is_active);
        $this->assertSame(
            '2026-08-10T09:00:00+01:00',
            $availability->starts_at?->toIso8601String(),
        );
        $this->assertSame(
            '2026-08-10T11:00:00+01:00',
            $availability->ends_at?->toIso8601String(),
        );

        $slots = $availability->slots
            ->sortBy('starts_at')
            ->values();

        $this->assertCount(4, $slots);
        $this->assertSame(
            [
                '2026-08-10T09:00:00+01:00',
                '2026-08-10T09:30:00+01:00',
                '2026-08-10T10:00:00+01:00',
                '2026-08-10T10:30:00+01:00',
            ],
            $slots->pluck('starts_at')
                ->map(
                    static fn ($date): ?string => $date?->toIso8601String(),
                )
                ->all(),
        );

        foreach ($slots as $slot) {
            $this->assertSame(
                $availability->municipality_id,
                $slot->municipality_id,
            );
            $this->assertSame(
                $availability->housing_unit_id,
                $slot->housing_unit_id,
            );
            $this->assertSame(
                MunicipalApplicationDemoVisitsExportSeeder::LOCATION,
                $slot->location,
            );
            $this->assertSame(
                MunicipalApplicationDemoVisitsExportSeeder::MEETING_POINT,
                $slot->meeting_point,
            );
            $this->assertSame(1, $slot->capacity);
        }
    }

    public function test_visit_is_booked_confirmed_rescheduled_and_completed(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $candidate = $this->candidate();
        $manager = $this->visitManager();
        $visit = HousingVisit::query()
            ->where('application_id', $application->id)
            ->with(['slot', 'statusHistories'])
            ->sole();

        $this->assertSame(VisitStatus::Completed, $visit->status);
        $this->assertSame($candidate->id, $visit->candidate_user_id);
        $this->assertSame($manager->id, $visit->staff_user_id);
        $this->assertSame(
            $this->housingUnitId($application),
            $visit->housing_unit_id,
        );
        $this->assertSame(
            MunicipalApplicationDemoVisitsExportSeeder::VISIT_CANDIDATE_NOTES,
            $visit->candidate_notes,
        );
        $this->assertSame(
            MunicipalApplicationDemoVisitsExportSeeder::VISIT_STAFF_NOTES,
            $visit->staff_notes,
        );
        $this->assertNotNull($visit->scheduled_at);
        $this->assertNotNull($visit->confirmed_at);
        $this->assertNotNull($visit->completed_at);
        $this->assertSame(
            '2026-08-10T09:30:00+01:00',
            $visit->starts_at?->toIso8601String(),
        );

        $history = $visit->statusHistories
            ->sortBy('id')
            ->values();

        $this->assertCount(4, $history);
        $this->assertSame(
            [
                VisitStatus::PendingConfirmation->value,
                VisitStatus::Confirmed->value,
                VisitStatus::Rescheduled->value,
                VisitStatus::Completed->value,
            ],
            $history
                ->pluck('to_status')
                ->map(
                    static fn ($status): string => $status instanceof VisitStatus
                            ? $status->value
                            : (string) $status,
                )
                ->all(),
        );
        $this->assertSame(
            MunicipalApplicationDemoVisitsExportSeeder::RESCHEDULE_REASON,
            $history->get(2)?->reason,
        );

        $slots = VisitSlot::query()
            ->where('visit_availability_id', $visit->slot?->visit_availability_id)
            ->orderBy('starts_at')
            ->get();

        $this->assertSame(VisitSlotStatus::Available, $slots[0]->status);
        $this->assertSame(0, $slots[0]->booked_count);
        $this->assertSame(VisitSlotStatus::Full, $slots[1]->status);
        $this->assertSame(1, $slots[1]->booked_count);

        $this->assertDatabaseHas('candidate_interactions', [
            'user_id' => $candidate->id,
            'interaction_type' => 'visit_scheduled',
        ]);
        $this->assertDatabaseHas('candidate_interactions', [
            'user_id' => $candidate->id,
            'interaction_type' => 'visit_rescheduled',
        ]);
        $this->assertDatabaseHas('candidate_interactions', [
            'user_id' => $candidate->id,
            'interaction_type' => 'visit_completed',
        ]);

        $this->assertSame(
            1,
            WorkTask::query()
                ->where('source', 'housing_visit:'.$visit->id)
                ->count(),
        );

        $notifications = OfficialNotification::query()
            ->where(
                'notifiable_type',
                $visit->getMorphClass(),
            )
            ->where('notifiable_id', $visit->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(4, $notifications);
        $this->assertSame(
            [
                'Visita solicitada',
                'Visita confirmada',
                'Visita reagendada',
                'Visita concluída',
            ],
            $notifications->pluck('subject')->all(),
        );
    }

    public function test_exporter_generates_private_html_and_csv_application_reports(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $exporter = $this->exporter();
        $reports = ApplicationReport::query()
            ->where('application_id', $application->id)
            ->orderBy('format')
            ->get();

        $this->assertCount(2, $reports);
        $this->assertSame(
            [
                ReportFormat::Csv->value,
                ReportFormat::Html->value,
            ],
            $reports->pluck('format')
                ->map(
                    static fn ($format): string => $format instanceof ReportFormat
                            ? $format->value
                            : (string) $format,
                )
                ->all(),
        );

        foreach ($reports as $report) {
            $this->assertSame(
                ApplicationReportStatus::Generated,
                $report->status,
            );
            $this->assertSame($exporter->id, $report->generated_by);
            $this->assertSame($candidateId = $application->user_id, $report->user_id);
            $this->assertSame(
                $application->application_number,
                $report->payload['application']['application_number']
                    ?? null,
            );
            $this->assertSame(
                'Candidato',
                $report->payload['candidate']['name'] ?? null,
            );
            $this->assertSame(
                15,
                count($report->payload['documents'] ?? []),
            );
            Storage::disk('local')->assertExists($report->file_path);

            $contents = Storage::disk('local')->get(
                $report->file_path,
            );

            $this->assertIsString($contents);
            $this->assertStringContainsString(
                (string) $application->application_number,
                $contents,
            );
            $this->assertStringNotContainsString(
                $this->candidate()->name,
                $contents,
            );
        }

        $this->assertSame($application->user_id, $candidateId);
        $this->assertSame(
            2,
            DB::table('audit_logs')
                ->where('module', 'reports')
                ->where(
                    'action',
                    'application_report_generate',
                )
                ->count(),
        );
    }

    public function test_document_dossier_indexes_all_current_private_documents(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $exporter = $this->exporter();
        $dossier = DocumentDossier::query()
            ->where('application_id', $application->id)
            ->with(['items.submission'])
            ->sole();

        $this->assertSame(
            DocumentDossierStatus::Standardized,
            $dossier->status,
        );
        $this->assertSame($exporter->id, $dossier->created_by);
        $this->assertSame(0, $dossier->missing_documents_count);
        $this->assertSame(0, $dossier->rejected_documents_count);
        $this->assertSame(0, $dossier->expired_documents_count);
        $this->assertSame(15, $dossier->validated_documents_count);
        $this->assertCount(15, $dossier->items);

        foreach ($dossier->items as $item) {
            $this->assertSame(
                DocumentDossierItemStatus::Validated,
                $item->status,
            );
            $this->assertTrue($item->is_required);
            $this->assertFalse($item->is_missing);
            $this->assertFalse($item->is_rejected);
            $this->assertFalse($item->is_expired);
            $this->assertTrue($item->is_validated);
            $this->assertInstanceOf(
                DocumentSubmission::class,
                $item->submission,
            );
            $this->assertSame(
                $application->id,
                $item->submission->application_id,
            );
        }

        $submissionIds = DocumentSubmission::query()
            ->where('application_id', $application->id)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame(
            $submissionIds,
            $dossier->items
                ->pluck('document_submission_id')
                ->sort()
                ->values()
                ->all(),
        );

        Storage::disk('local')->assertExists($dossier->file_path);
        $contents = Storage::disk('local')->get($dossier->file_path);

        $this->assertIsString($contents);
        $this->assertStringContainsString(
            (string) $application->application_number,
            $contents,
        );
        $preferenceRowCount = collect(
            data_get(
                $dossier->standardization_payload,
                'housing_preferences',
                [],
            ),
        )
            ->filter(
                static fn (mixed $preference): bool => is_array($preference),
            )
            ->count();

        $this->assertSame(
            2 + $preferenceRowCount + $dossier->items->count(),
            substr_count($contents, '<tr>'),
        );

        $this->assertSame(
            1,
            DB::table('audit_logs')
                ->where('module', 'documents')
                ->where(
                    'action',
                    'document_dossier_generate',
                )
                ->count(),
        );
    }

    public function test_visit_and_export_profiles_remain_segregated(): void
    {
        $this->seedDemo();

        $manager = $this->visitManager();
        $exporter = $this->exporter();

        $this->assertTrue($manager->hasPermission('visits.create'));
        $this->assertTrue($manager->hasPermission('visits.confirm'));
        $this->assertTrue($manager->hasPermission('visits.complete'));
        $this->assertFalse(
            $manager->hasPermission('applications.export'),
        );
        $this->assertFalse($manager->hasPermission('reports.export'));

        $this->assertTrue(
            $exporter->hasPermission('applications.export'),
        );
        $this->assertTrue($exporter->hasPermission('reports.export'));
        $this->assertTrue($exporter->hasPermission('reports.audit'));
        $this->assertFalse($exporter->hasPermission('visits.create'));
        $this->assertFalse($exporter->hasPermission('visits.confirm'));

        $this->assertSame(
            MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_ROLE_NAME,
            $manager->roles()->sole()->name,
        );
        $this->assertSame(
            MunicipalApplicationDemoAccessSeeder::EXPORTER_ROLE_NAME,
            $exporter->roles()->sole()->name,
        );
    }

    public function test_complete_orchestrator_is_idempotent_after_visits_and_exports(): void
    {
        $this->seedDemo();

        $application = $this->application();
        $first = $this->stableState($application);

        $this->seedDemo();

        $application = $this->application();
        $second = $this->stableState($application);

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('housing_visits', 1);
        $this->assertDatabaseCount('visit_availabilities', 1);
        $this->assertDatabaseCount('visit_slots', 4);
        $this->assertDatabaseCount('application_reports', 2);
        $this->assertDatabaseCount('document_dossiers', 1);
        $this->assertDatabaseCount('document_dossier_items', 15);
        $this->assertDatabaseCount('application_snapshots', 8);
        $this->assertDatabaseCount('application_documents', 15);
        $this->assertDatabaseCount('document_submissions', 15);
        $this->assertDatabaseCount('document_versions', 16);
        $this->assertDatabaseCount('document_ai_analyses', 0);
        Queue::assertNothingPushed();
    }

    private function seedDemo(): void
    {
        $this->assertTrue(
            class_exists(
                MunicipalApplicationDemoVisitsExportSeeder::class,
            ),
            'Falta implementar o seeder combinado 51F/51G.',
        );

        $this->seed(MunicipalApplicationDemoSeeder::class);
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
            ->with([
                'housingPreferences',
                'snapshots',
            ])
            ->sole();
    }

    private function candidate(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::CANDIDATE_EMAIL,
            )
            ->sole();
    }

    private function visitManager(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::VISIT_MANAGER_EMAIL,
            )
            ->sole();
    }

    private function exporter(): User
    {
        return User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::EXPORTER_EMAIL,
            )
            ->sole();
    }

    private function housingUnitId(
        Application $application,
    ): int {
        return (int) $application->housingPreferences
            ->sortBy('preference_order')
            ->firstOrFail()
            ->housing_unit_id;
    }

    /**
     * @return array<string, mixed>
     */
    private function stableState(
        Application $application,
    ): array {
        $availability = VisitAvailability::query()
            ->where('contest_id', $application->contest_id)
            ->with('slots')
            ->sole();
        $visit = HousingVisit::query()
            ->where('application_id', $application->id)
            ->with('statusHistories')
            ->sole();

        return [
            'application' => [
                'id' => $application->id,
                'number' => $application->application_number,
                'status' => $application->status->value,
            ],
            'availability' => [
                'id' => $availability->id,
                'starts_at' => $availability->starts_at?->toIso8601String(),
                'ends_at' => $availability->ends_at?->toIso8601String(),
                'slots' => $availability->slots
                    ->sortBy('starts_at')
                    ->map(
                        static fn (VisitSlot $slot): array => [
                            'id' => $slot->id,
                            'status' => $slot->status->value,
                            'booked_count' => $slot->booked_count,
                            'starts_at' => $slot->starts_at?->toIso8601String(),
                        ],
                    )
                    ->values()
                    ->all(),
            ],
            'visit' => [
                'id' => $visit->id,
                'number' => $visit->visit_number,
                'slot_id' => $visit->visit_slot_id,
                'status' => $visit->status->value,
                'histories' => $visit->statusHistories
                    ->sortBy('id')
                    ->map(
                        static fn ($history): array => [
                            'id' => $history->id,
                            'from' => $history->from_status,
                            'to' => $history->to_status,
                            'changed_by' => $history->changed_by,
                        ],
                    )
                    ->values()
                    ->all(),
            ],
            'reports' => ApplicationReport::query()
                ->where('application_id', $application->id)
                ->orderBy('id')
                ->get()
                ->map(
                    static fn (
                        ApplicationReport $report,
                    ): array => [
                        'id' => $report->id,
                        'number' => $report->report_number,
                        'format' => $report->format->value,
                        'path' => $report->file_path,
                        'hash' => hash(
                            'sha256',
                            Storage::disk('local')
                                ->get($report->file_path),
                        ),
                    ],
                )
                ->all(),
            'dossier' => DocumentDossier::query()
                ->where('application_id', $application->id)
                ->with('items')
                ->get()
                ->map(
                    static fn (
                        DocumentDossier $dossier,
                    ): array => [
                        'id' => $dossier->id,
                        'number' => $dossier->dossier_number,
                        'status' => $dossier->status->value,
                        'path' => $dossier->file_path,
                        'items' => $dossier->items
                            ->pluck('id')
                            ->all(),
                        'hash' => hash(
                            'sha256',
                            Storage::disk('local')
                                ->get($dossier->file_path),
                        ),
                    ],
                )
                ->all(),
            'notifications' => OfficialNotification::query()
                ->where(
                    'notifiable_type',
                    $visit->getMorphClass(),
                )
                ->where('notifiable_id', $visit->id)
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
                    ],
                )
                ->all(),
            'counts' => [
                'candidate_interactions' => DB::table('candidate_interactions')->count(),
                'work_tasks' => DB::table('work_tasks')->count(),
                'audit_logs' => DB::table('audit_logs')->count(),
                'communication_logs' => DB::table('communication_logs')->count(),
                'communication_deliveries' => DB::table('communication_deliveries')->count(),
                'document_versions' => DocumentVersion::query()->count(),
            ],
        ];
    }
}
