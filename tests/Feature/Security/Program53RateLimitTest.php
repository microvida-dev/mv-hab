<?php

namespace Tests\Feature\Security;

use App\Enums\ApplicationResultExportDataset;
use App\Enums\ApplicationResultExportFormat;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ExportScope;
use App\Enums\FeatureKey;
use App\Enums\ReportExportStatus;
use App\Enums\ReportFormat;
use App\Enums\ReportRunStatus;
use App\Jobs\GenerateApplicationResultExport;
use App\Models\ApplicationReviewBatch;
use App\Models\AuditLog;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Reporting\Temporal\TemporalApplicationResultExportService;
use App\Services\Security\Program53RateLimitService;
use Database\Seeders\ReportDefinitionSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Program53RateLimitTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private Municipality $municipality;

    private Contest $contest;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Storage::fake('local');
        $this->seed([SystemAccessSeeder::class, ReportDefinitionSeeder::class]);
        $this->municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
            FeatureKey::ApplicationExport,
        );
        $this->contest = $this->contestFor($this->municipality);
    }

    public function test_preview_allows_last_attempt_then_returns_audited_deduplicated_429(): void
    {
        $this->configureLimit(Program53RateLimitService::EXPORT_PREVIEW, 2, 20);
        $actor = $this->administrator($this->municipality);

        $this->postAs($actor, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertOk();
        $this->postAs($actor, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertOk();

        $blocked = $this->postAs(
            $actor,
            'backoffice.reports.temporal-exports.preview',
            $this->payload(),
        );
        $blocked->assertTooManyRequests();
        $this->assertNotNull($blocked->headers->get('Retry-After'));
        $this->assertDatabaseCount('report_exports', 0);

        $this->postAs($actor, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertTooManyRequests();
        $this->assertSame(1, AuditLog::query()
            ->where('event', 'program53_rate_limit_exceeded')
            ->where('user_id', $actor->id)
            ->where('action', 'throttle')
            ->count());
        $metadata = AuditLog::query()
            ->where('event', 'program53_rate_limit_exceeded')
            ->firstOrFail()
            ->metadata;
        $encoded = json_encode($metadata, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($actor->email, $encoded);
        $this->assertStringNotContainsString('contest_id', $encoded);
    }

    public function test_user_and_municipality_dimensions_are_independent(): void
    {
        $this->configureLimit(Program53RateLimitService::EXPORT_PREVIEW, 1, 2);
        $first = $this->administrator($this->municipality);
        $second = $this->administrator($this->municipality);
        $third = $this->administrator($this->municipality);

        $this->postAs($first, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertOk();
        $this->postAs($first, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertTooManyRequests();
        $this->postAs($second, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertOk();
        $this->postAs($third, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertTooManyRequests();

        $otherMunicipality = $this->municipalityWithFeatures(FeatureKey::ApplicationExport);
        $otherContest = $this->contestFor($otherMunicipality);
        $otherActor = $this->administrator($otherMunicipality);
        $this->postAs(
            $otherActor,
            'backoffice.reports.temporal-exports.preview',
            $this->payload(['contest_id' => $otherContest->id]),
        )->assertOk();
    }

    public function test_sensitive_export_profile_is_more_restrictive_than_normal_profile(): void
    {
        $this->configureLimit(
            Program53RateLimitService::EXPORT_PREVIEW,
            2,
            20,
            sensitiveUserAttempts: 1,
            sensitiveMunicipalityAttempts: 10,
        );
        $normalActor = $this->administrator($this->municipality);
        $sensitiveActor = $this->administrator($this->municipality);

        $this->postAs($normalActor, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertOk();
        $this->postAs($normalActor, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertOk();
        $this->postAs($normalActor, 'backoffice.reports.temporal-exports.preview', $this->payload())
            ->assertTooManyRequests();

        $sensitivePayload = $this->payload([
            'include_sensitive' => '1',
            'sensitive_confirmed' => '1',
        ]);
        $this->postAs($sensitiveActor, 'backoffice.reports.temporal-exports.preview', $sensitivePayload)
            ->assertOk();
        $this->postAs($sensitiveActor, 'backoffice.reports.temporal-exports.preview', $sensitivePayload)
            ->assertTooManyRequests();
    }

    public function test_rejected_export_request_creates_no_second_export_or_job(): void
    {
        $this->configureLimit(Program53RateLimitService::EXPORT_REQUEST, 1, 10);
        $actor = $this->administrator($this->municipality);

        $this->postAs($actor, 'backoffice.reports.temporal-exports.store', $this->payload())
            ->assertRedirect();
        $this->assertDatabaseCount('report_exports', 1);
        $this->assertDatabaseCount('report_runs', 1);
        Queue::assertPushed(GenerateApplicationResultExport::class, 1);

        $this->postAs(
            $actor,
            'backoffice.reports.temporal-exports.store',
            $this->payload(['idempotency_token' => (string) Str::uuid()]),
        )->assertTooManyRequests();

        $this->assertDatabaseCount('report_exports', 1);
        $this->assertDatabaseCount('report_runs', 1);
        Queue::assertPushed(GenerateApplicationResultExport::class, 1);
    }

    public function test_rejected_download_does_not_create_a_second_download_log(): void
    {
        $this->configureLimit(Program53RateLimitService::EXPORT_DOWNLOAD, 1, 10);
        $actor = $this->administrator($this->municipality);
        $export = $this->completedExport($actor);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.download', $export))
            ->assertOk();
        $this->assertDatabaseCount('report_download_logs', 1);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.reports.temporal-exports.download', $export))
            ->assertTooManyRequests();
        $this->assertDatabaseCount('report_download_logs', 1);
    }

    public function test_rejected_seal_and_publication_do_not_create_domain_records(): void
    {
        $this->configureLimit(Program53RateLimitService::BATCH_SEAL, 1, 10);
        $this->configureLimit(Program53RateLimitService::BATCH_PUBLISH, 1, 10);
        $actor = $this->administrator($this->municipality);

        $this->postAs($actor, 'backoffice.application-review-batches.seal', [], $this->contest)
            ->assertRedirect();
        $this->postAs($actor, 'backoffice.application-review-batches.seal', [], $this->contest)
            ->assertTooManyRequests();
        $this->assertDatabaseCount('application_review_batches', 0);

        $batch = ApplicationReviewBatch::query()->create([
            'municipality_id' => $this->municipality->id,
            'contest_id' => $this->contest->id,
            'cycle' => ApplicationReviewBatchCycle::InitialReview,
            'sequence_number' => 1,
            'status' => ApplicationReviewBatchStatus::Sealed,
            'reason' => 'Fixture técnica para testar o limitador.',
            'item_count' => 0,
            'seal_key' => hash('sha256', 'rate-limit-seal'),
            'source_fingerprint' => hash('sha256', 'rate-limit-source'),
            'snapshot_hash' => hash('sha256', 'rate-limit-snapshot'),
            'sealed_by' => $actor->id,
            'sealed_at' => now(),
        ]);

        $this->postAs($actor, 'backoffice.application-review-publications.publish', [], $batch)
            ->assertRedirect();
        $this->postAs($actor, 'backoffice.application-review-publications.publish', [], $batch)
            ->assertTooManyRequests();
        $this->assertDatabaseCount('application_review_publications', 0);
    }

    public function test_critical_routes_use_only_the_expected_named_limiters(): void
    {
        $expected = [
            'backoffice.reports.temporal-exports.preview' => 'throttle:program53.export-preview',
            'backoffice.reports.temporal-exports.store' => 'throttle:program53.export-request',
            'backoffice.reports.temporal-exports.download' => 'throttle:program53.export-download',
            'backoffice.application-review-batches.seal' => 'throttle:program53.batch-seal',
            'backoffice.application-review-publications.publish' => 'throttle:program53.batch-publish',
            'backoffice.correction-revalidations.seal' => 'throttle:program53.revalidation-seal',
        ];

        foreach ($expected as $routeName => $limiter) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, $routeName);
            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );
            $this->assertContains($limiter, $middleware, $routeName);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'contest_id' => $this->contest->id,
            'mode' => ApplicationResultExportMode::CurrentState->value,
            'formats' => [ApplicationResultExportFormat::Csv->value],
            'datasets' => [ApplicationResultExportDataset::Applications->value],
            'csv_delimiter' => 'semicolon',
            'csv_bom' => '1',
            'include_sensitive' => '0',
            'include_document_files' => '0',
            'changed_documents_only' => '0',
            'include_unchanged' => '0',
            'idempotency_token' => (string) Str::uuid(),
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return TestResponse<Response>
     */
    private function postAs(
        User $actor,
        string $routeName,
        array $payload,
        mixed $parameter = null,
    ): TestResponse {
        $route = $parameter === null ? route($routeName) : route($routeName, $parameter);

        return $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post($route, $payload);
    }

    private function configureLimit(
        string $operation,
        int $userAttempts,
        int $municipalityAttempts,
        int $decaySeconds = 60,
        ?int $sensitiveUserAttempts = null,
        ?int $sensitiveMunicipalityAttempts = null,
    ): void {
        Config::set("mvhab.rate_limits.program53.{$operation}.normal", [
            'user' => [
                'max_attempts' => $userAttempts,
                'decay_seconds' => $decaySeconds,
            ],
            'municipality' => [
                'max_attempts' => $municipalityAttempts,
                'decay_seconds' => $decaySeconds,
            ],
        ]);

        if ($sensitiveUserAttempts !== null && $sensitiveMunicipalityAttempts !== null) {
            Config::set("mvhab.rate_limits.program53.{$operation}.sensitive", [
                'user' => [
                    'max_attempts' => $sensitiveUserAttempts,
                    'decay_seconds' => $decaySeconds,
                ],
                'municipality' => [
                    'max_attempts' => $sensitiveMunicipalityAttempts,
                    'decay_seconds' => $decaySeconds,
                ],
            ]);
        }
    }

    private function administrator(Municipality $municipality): User
    {
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $user->assignRole('administrator');

        return $user;
    }

    private function contestFor(Municipality $municipality): Contest
    {
        $program = Program::factory()->create(['municipality_id' => $municipality->id]);

        return Contest::factory()->create(['program_id' => $program->id]);
    }

    private function completedExport(User $owner): ReportExport
    {
        $definition = ReportDefinition::query()
            ->where('code', TemporalApplicationResultExportService::REPORT_CODE)
            ->firstOrFail();
        $run = ReportRun::factory()->create([
            'report_definition_id' => $definition->id,
            'user_id' => $owner->id,
            'status' => ReportRunStatus::Completed,
            'format' => ReportFormat::Zip,
            'scope' => ExportScope::Pseudonymized,
            'filters' => ['contest_id' => $this->contest->id],
        ]);
        $path = 'reports/tests/'.Str::uuid().'/export.zip';
        Storage::disk('local')->put($path, 'zip-test');

        return ReportExport::factory()->create([
            'report_run_id' => $run->id,
            'user_id' => $owner->id,
            'municipality_id' => $this->municipality->id,
            'contest_id' => $this->contest->id,
            'export_profile' => TemporalApplicationResultExportService::PROFILE,
            'export_mode' => ApplicationResultExportMode::CurrentState,
            'status' => ReportExportStatus::Completed,
            'requested_format' => ReportFormat::Zip,
            'format' => ReportFormat::Zip,
            'scope' => ExportScope::Pseudonymized,
            'file_path' => $path,
            'file_name' => 'export.zip',
            'formats' => [ApplicationResultExportFormat::Csv->value],
            'datasets' => [ApplicationResultExportDataset::Applications->value],
            'expires_at' => now()->addHour(),
        ]);
    }
}
