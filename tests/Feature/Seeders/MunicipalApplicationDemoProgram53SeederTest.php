<?php

namespace Tests\Feature\Seeders;

use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\ReportExportStatus;
use App\Models\Application;
use App\Models\ApplicationReviewBatch;
use App\Models\CorrectionRequest;
use App\Models\CorrectionSubmissionReceipt;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Demo\MunicipalApplicationDemoSummaryService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Support\CanonicalJsonHasher;
use Carbon\CarbonImmutable;
use Database\Seeders\Demo\MunicipalApplicationDemoAccessSeeder;
use Database\Seeders\Demo\MunicipalApplicationDemoProgram53Seeder;
use Database\Seeders\Demo\MunicipalApplicationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MunicipalApplicationDemoProgram53SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'testing';
        config()->set('cache.default', 'array');
        config()->set('mvhab.regulatory_demo_mode', true);
        config()->set('mvhab.municipal_application_demo.enabled', true);
        config()->set(
            'mvhab.municipal_application_demo.reference_date',
            '2026-07-27',
        );
        config()->set(
            'mvhab.municipal_application_demo.user_password',
            'MVHAB-Demo-2026!',
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

    public function test_seeder_covers_the_complete_program_53_demo_cycle(): void
    {
        $this->seed(MunicipalApplicationDemoSeeder::class);

        $summary = app(
            MunicipalApplicationDemoSummaryService::class,
        )->verify();
        $this->assertSame(2, data_get($summary, 'counts.municipalities'));
        $this->assertSame(3, data_get($summary, 'counts.review_batch_items'));
        $this->assertSame(2, data_get($summary, 'counts.temporal_exports'));
        $this->assertTrue(data_get(
            $summary,
            'program53.municipality_isolation.cross_access_denied',
        ));

        $primaryApplication = Application::query()
            ->where(
                'application_number',
                'CAND-2026-ALC-DEMO-CAND-01-2026-000001',
            )
            ->sole();
        $batches = ApplicationReviewBatch::query()
            ->where('contest_id', $primaryApplication->contest_id)
            ->orderBy('sequence_number')
            ->get();
        $this->assertSame([
            ApplicationReviewBatchCycle::InitialReview,
            ApplicationReviewBatchCycle::Revalidation,
        ], $batches->pluck('cycle')->all());
        $this->assertTrue($batches->every(
            static fn (ApplicationReviewBatch $batch): bool => $batch->status
                === ApplicationReviewBatchStatus::Sealed,
        ));
        $this->assertSame([2, 1], $batches->pluck('item_count')->all());

        $receipt = CorrectionSubmissionReceipt::query()
            ->where('application_id', $primaryApplication->id)
            ->sole();
        $this->assertSame(
            $receipt->snapshot_hash,
            app(CanonicalJsonHasher::class)->hash(
                $receipt->snapshot_payload,
            ),
        );

        $noResponseApplication = Application::query()
            ->where(
                'application_number',
                MunicipalApplicationDemoProgram53Seeder::NO_RESPONSE_APPLICATION_NUMBER,
            )
            ->sole();
        $expired = CorrectionRequest::query()
            ->where('application_id', $noResponseApplication->id)
            ->sole();
        $this->assertSame(CorrectionRequestStatus::Expired, $expired->status);
        $this->assertFalse($expired->responses()->exists());
        $this->assertFalse($expired->submissionReceipt()->exists());

        $exports = ReportExport::query()
            ->orderBy('export_mode')
            ->get();
        $this->assertSame([
            ApplicationResultExportMode::DeltaBetweenBatches,
            ApplicationResultExportMode::SealedBatch,
        ], $exports->pluck('export_mode')->all());
        foreach ($exports as $export) {
            $this->assertSame(ReportExportStatus::Completed, $export->status);
            $this->assertFalse($export->sensitive_fields_included);
            $this->assertFalse($export->document_files_included);
            Storage::disk('local')->assertExists($export->file_path);
        }

        $primaryAnalyst = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoAccessSeeder::ANALYST_EXPORT_EMAIL,
            )
            ->sole();
        $controlAnalyst = User::query()
            ->where(
                'email',
                MunicipalApplicationDemoProgram53Seeder::CONTROL_ANALYST_EMAIL,
            )
            ->sole();
        $scope = app(MunicipalRecordScopeService::class);
        $this->assertSame(
            2,
            $scope->applications(Application::query(), $primaryAnalyst)
                ->count(),
        );
        $this->assertSame(
            1,
            $scope->applications(Application::query(), $controlAnalyst)
                ->count(),
        );

        Queue::assertNothingPushed();
    }
}
