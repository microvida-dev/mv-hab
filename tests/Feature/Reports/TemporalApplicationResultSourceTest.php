<?php

namespace Tests\Feature\Reports;

use App\Enums\ApplicationResultChangeType;
use App\Enums\ApplicationResultExportMode;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ApplicationReviewPublicationStatus;
use App\Enums\ApplicationStatus;
use App\Enums\CommunicationChannel;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationReviewPublication;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\CommunicationDelivery;
use App\Models\CommunicationLog;
use App\Models\Contest;
use App\Models\Municipality;
use App\Models\OfficialNotification;
use App\Models\Program;
use App\Models\User;
use App\Services\Reporting\Temporal\ApplicationResultExportSnapshotBuilder;
use App\Services\Reporting\Temporal\ApplicationResultExportSourceResolver;
use App\Services\Reporting\Temporal\CanonicalNdjsonStore;
use App\Services\Support\CanonicalJsonHasher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TemporalApplicationResultSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-08-01 15:00:00 UTC');
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_resolver_supports_all_temporal_modes_with_authoritative_sources(): void
    {
        $fixture = $this->temporalFixture();
        $resolver = app(ApplicationResultExportSourceResolver::class);

        $current = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::CurrentState,
        );
        $sealed = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::SealedBatch,
            ['batch_public_id' => $fixture['t1']->public_id],
        );
        $phase = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::PhaseSnapshot,
            [
                'phase' => ApplicationReviewBatchCycle::InitialReview->value,
                'as_of' => '2026-08-01T11:00:00Z',
            ],
        );
        $between = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::DeltaBetweenBatches,
            [
                'base_batch_public_id' => $fixture['t1']->public_id,
                'target_batch_public_id' => $fixture['t2']->public_id,
            ],
        );
        $since = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::DeltaSinceDatetime,
            [
                'since' => '2026-08-01T11:00:00Z',
                'as_of' => '2026-08-01T13:00:00Z',
            ],
        );
        $final = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::FinalResult,
            ['as_of' => '2026-08-01T13:00:00Z'],
        );

        $this->assertFalse($current->official);
        $this->assertSame($fixture['t1']->id, $sealed->batchId);
        $this->assertTrue($phase->official);
        $this->assertSame($fixture['t1']->id, $phase->batchId);
        $this->assertSame($fixture['t1']->id, $between->baseBatchId);
        $this->assertSame($fixture['t2']->id, $between->targetBatchId);
        $this->assertSame($fixture['t1']->id, $since->baseBatchId);
        $this->assertSame($fixture['t2']->id, $since->targetBatchId);
        $this->assertTrue($final->official);
        $this->assertSame(2, $final->sourceReferences['publication_count']);
    }

    public function test_delta_since_datetime_fails_closed_without_a_reconstructible_baseline(): void
    {
        $fixture = $this->temporalFixture();

        try {
            app(ApplicationResultExportSourceResolver::class)->resolve(
                $fixture['contest'],
                ApplicationResultExportMode::DeltaSinceDatetime,
                [
                    'since' => '2026-08-01T09:00:00Z',
                    'as_of' => '2026-08-01T13:00:00Z',
                ],
            );
            $this->fail('Era esperada uma falha fechada por ausência de baseline.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'source_not_found',
                $exception->errors()['failure_code'][0] ?? null,
            );
        }
    }

    public function test_sealed_source_is_scoped_to_the_contest_municipality(): void
    {
        $fixture = $this->temporalFixture();
        $foreignContest = Contest::factory()->create();

        $this->expectException(ValidationException::class);
        app(ApplicationResultExportSourceResolver::class)->resolve(
            $foreignContest,
            ApplicationResultExportMode::SealedBatch,
            ['batch_public_id' => $fixture['t1']->public_id],
        );
    }

    public function test_canonical_snapshot_is_reproducible_and_delta_contains_exact_changes(): void
    {
        $fixture = $this->temporalFixture();
        $resolver = app(ApplicationResultExportSourceResolver::class);
        $builder = app(ApplicationResultExportSnapshotBuilder::class);
        $store = app(CanonicalNdjsonStore::class);
        $source = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::SealedBatch,
            ['batch_public_id' => $fixture['t1']->public_id],
        );

        $first = $builder->build($source, 'report-exports/t1-a');
        $second = $builder->build($source, 'report-exports/t1-b');
        $applicationRows = iterator_to_array(
            $store->rows($first->datasetPaths['applications']),
            false,
        );

        $this->assertSame($first->sourceFingerprint, $second->sourceFingerprint);
        $this->assertSame($first->checksums, $second->checksums);
        $this->assertSame(1, $first->counts['applications']);
        $this->assertSame('APP-TEMP-001', $applicationRows[0]['application_number']);
        $this->assertSame(
            ApplicationReviewBatchOutcome::CorrectionRequired->value,
            $applicationRows[0]['review_result_code'],
        );
        $this->assertSame($first->sourceFingerprint, $applicationRows[0]['source_fingerprint']);

        $deltaSource = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::DeltaBetweenBatches,
            [
                'base_batch_public_id' => $fixture['t1']->public_id,
                'target_batch_public_id' => $fixture['t2']->public_id,
            ],
        );
        $delta = $builder->build($deltaSource, 'report-exports/delta');
        $changes = iterator_to_array(
            $store->rows($delta->datasetPaths['changes']),
            false,
        );

        $this->assertGreaterThanOrEqual(3, $delta->counts['changes']);
        $this->assertContains(
            ApplicationResultChangeType::Changed->value,
            array_column($changes, 'change_type'),
        );
        $this->assertContains('review_result_code', array_column($changes, 'field_code'));
        $this->assertContains('document_status_code', array_column($changes, 'field_code'));
        $this->assertContains('finding_status_code', array_column($changes, 'field_code'));
        $this->assertContains('carried_forward', array_column($changes, 'field_code'));

        $finalSource = $resolver->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::FinalResult,
            ['as_of' => '2026-08-01T13:00:00Z'],
        );
        $final = $builder->build($finalSource, 'report-exports/final');
        $finalRows = iterator_to_array(
            $store->rows($final->datasetPaths['applications']),
            false,
        );
        $this->assertSame(1, $final->counts['applications']);
        $this->assertSame(
            ApplicationReviewBatchOutcome::CompletePendingDecision->value,
            $finalRows[0]['review_result_code'],
        );
    }

    public function test_final_result_fails_closed_when_the_published_payload_hash_is_stale(): void
    {
        $fixture = $this->temporalFixture();
        DB::table('application_review_publication_results')->update([
            'result_hash' => str_repeat('0', 64),
        ]);
        $source = app(ApplicationResultExportSourceResolver::class)->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::FinalResult,
            ['as_of' => '2026-08-01T13:00:00Z'],
        );

        try {
            app(ApplicationResultExportSnapshotBuilder::class)->build(
                $source,
                'report-exports/stale-final',
            );
            $this->fail('Era esperada uma falha fechada por hash oficial inválido.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'source_stale',
                $exception->errors()['failure_code'][0] ?? null,
            );
            Storage::disk('local')->assertMissing('report-exports/stale-final');
        }
    }

    public function test_current_state_uses_bounded_chunks_and_exports_every_application(): void
    {
        $fixture = $this->temporalFixture();
        $base = $fixture['application'];
        $timestamp = CarbonImmutable::parse('2026-08-01T14:00:00Z');
        $rows = [];
        for ($index = 2; $index <= 251; $index++) {
            $rows[] = [
                'public_id' => (string) Str::uuid(),
                'application_number' => sprintf('APP-TEMP-%03d', $index),
                'user_id' => $base->user_id,
                'adhesion_registration_id' => $base->adhesion_registration_id,
                'program_id' => $base->program_id,
                'contest_id' => $base->contest_id,
                'household_id' => $base->household_id,
                'current_housing_situation_id' => $base->current_housing_situation_id,
                'status' => ApplicationStatus::Submitted->value,
                'submitted_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('applications')->insert($chunk);
        }

        $applicationSelects = 0;
        DB::listen(function (QueryExecuted $query) use (&$applicationSelects): void {
            if (
                str_contains(strtolower($query->sql), 'from "applications"')
                && str_contains(strtolower($query->sql), 'limit 250')
            ) {
                $applicationSelects++;
            }
        });
        $source = app(ApplicationResultExportSourceResolver::class)->resolve(
            $fixture['contest'],
            ApplicationResultExportMode::CurrentState,
        );
        $snapshot = app(ApplicationResultExportSnapshotBuilder::class)->build(
            $source,
            'report-exports/current-state',
        );

        $this->assertSame(251, $snapshot->counts['applications']);
        $this->assertGreaterThanOrEqual(4, $applicationSelects);
    }

    /**
     * @return array{
     *     municipality: Municipality,
     *     contest: Contest,
     *     application: Application,
     *     t1: ApplicationReviewBatch,
     *     t2: ApplicationReviewBatch
     * }
     */
    private function temporalFixture(): array
    {
        $municipality = Municipality::factory()->create(['code' => 'MUN-TEMP']);
        $program = Program::factory()->create(['municipality_id' => $municipality->id]);
        $contest = Contest::factory()->create([
            'program_id' => $program->id,
            'code' => 'CONC-TEMP-2026',
        ]);
        $candidate = User::factory()->create(['municipality_id' => $municipality->id]);
        $application = Application::factory()->submitted()->create([
            'application_number' => 'APP-TEMP-001',
            'user_id' => $candidate->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'submitted_at' => '2026-08-01 08:00:00',
            'created_at' => '2026-08-01 07:30:00',
            'updated_at' => '2026-08-01 08:00:00',
        ]);
        $process = AdministrativeProcess::factory()->create([
            'application_id' => $application->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'user_id' => $candidate->id,
            'process_number' => 'PROC-TEMP-001',
        ]);
        $actor = User::factory()->create(['municipality_id' => $municipality->id]);

        $t1Payload = $this->payload(
            $application,
            $process,
            ApplicationReviewBatchOutcome::CorrectionRequired,
            'missing',
        );
        $t2Payload = $this->payload(
            $application,
            $process,
            ApplicationReviewBatchOutcome::CompletePendingDecision,
            'accepted',
        );
        $t1 = $this->batch(
            $contest,
            $municipality,
            $actor,
            $application,
            $process,
            ApplicationReviewBatchCycle::InitialReview,
            1,
            '2026-08-01 09:30:00',
            $t1Payload,
        );
        $t2 = $this->batch(
            $contest,
            $municipality,
            $actor,
            $application,
            $process,
            ApplicationReviewBatchCycle::Revalidation,
            2,
            '2026-08-01 11:30:00',
            $t2Payload,
        );
        $this->publication($t1, $actor, '2026-08-01 10:00:00');
        $latestPublication = $this->publication($t2, $actor, '2026-08-01 12:00:00');
        $this->publicationResult(
            $latestPublication,
            $t2,
            $application,
            $process,
            $actor,
        );

        return compact('municipality', 'contest', 'application', 't1', 't2');
    }

    /** @return array<string, mixed> */
    private function payload(
        Application $application,
        AdministrativeProcess $process,
        ApplicationReviewBatchOutcome $outcome,
        string $findingStatus,
    ): array {
        $isCorrection = $outcome === ApplicationReviewBatchOutcome::CorrectionRequired;

        return [
            'schema_version' => $isCorrection ? 2 : 1,
            'process' => [
                'id' => $process->id,
                'number' => $process->process_number,
                'status' => $process->status->value,
                'application_id' => $application->id,
                'contest_id' => $application->contest_id,
                'program_id' => $application->program_id,
            ],
            'application' => [
                'id' => $application->id,
                'public_id' => $application->public_id,
                'number' => $application->application_number,
                'status' => $application->status->value,
                'submitted_at' => $application->submitted_at?->toIso8601String(),
                'program_id' => $application->program_id,
                'contest_id' => $application->contest_id,
            ],
            'outcome' => $outcome->value,
            'technical_result' => $isCorrection ? 'requires_correction' : 'passed',
            'review' => $isCorrection ? ['status' => 'completed'] : null,
            'readiness' => [
                'ready' => ! $isCorrection,
                'total_required' => 2,
                'validated' => $isCorrection ? 1 : 2,
                'missing' => $isCorrection ? 1 : 0,
                'rejected' => 0,
                'expired' => 0,
                'blockers' => $isCorrection ? ['Documento em falta'] : [],
            ],
            'documents' => [
                [
                    'key' => 'document:10',
                    'required_document_id' => 10,
                    'document_type_id' => 20,
                    'requirement_instance' => 1,
                    'status' => $isCorrection ? 'missing' : 'validated',
                    'classification' => $isCorrection ? 'missing' : 'changed_document',
                    'checksum' => $isCorrection ? 'checksum-t1' : 'checksum-t2',
                    'target' => ['application_id' => $application->id],
                ],
                [
                    'key' => 'document:11',
                    'required_document_id' => 11,
                    'document_type_id' => 21,
                    'requirement_instance' => 1,
                    'status' => 'validated',
                    'classification' => $isCorrection ? 'accepted' : 'unchanged_valid',
                    'checksum' => 'checksum-carried-forward',
                    'target' => ['application_id' => $application->id],
                ],
            ],
            'findings' => $isCorrection ? [[
                'key' => 'finding:10',
                'required_document_id' => 10,
                'finding_status' => 'missing',
            ]] : [],
            'correction_request' => $isCorrection ? null : [
                'submitted_at' => '2026-08-01T11:00:00+00:00',
            ],
            'carried_forward_items' => $isCorrection ? [] : [[
                'key' => 'document:11',
                'required_document_id' => 11,
                'requirement_instance' => 1,
                'classification' => 'unchanged_valid',
                'submitted_checksum' => 'checksum-carried-forward',
            ]],
            'decisions' => $isCorrection ? [] : [[
                'key' => 'finding:10',
                'required_document_id' => 10,
                'result' => $findingStatus,
                'reviewed_at' => '2026-08-01T11:15:00+00:00',
            ]],
            'aggregate_result' => $isCorrection ? null : [
                'value' => 'accepted',
                'label' => 'Aperfeiçoamento aceite',
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function batch(
        Contest $contest,
        Municipality $municipality,
        User $actor,
        Application $application,
        AdministrativeProcess $process,
        ApplicationReviewBatchCycle $cycle,
        int $sequence,
        string $sealedAt,
        array $payload,
    ): ApplicationReviewBatch {
        $hasher = app(CanonicalJsonHasher::class);
        $snapshotHash = $hasher->hash($payload);
        $batchHash = $hasher->hash([
            'schema_version' => 1,
            'contest_id' => $contest->id,
            'cycle' => $cycle->value,
            'items' => [[
                'application_id' => $application->id,
                'snapshot_hash' => $snapshotHash,
                'payload' => $payload,
            ]],
        ]);
        $batch = new ApplicationReviewBatch;
        $batch->forceFill([
            'municipality_id' => $municipality->id,
            'contest_id' => $contest->id,
            'cycle' => $cycle,
            'sequence_number' => $sequence,
            'status' => ApplicationReviewBatchStatus::Sealed,
            'reason' => 'Fonte temporal de teste.',
            'item_count' => 1,
            'seal_key' => hash('sha256', 'seal-'.$cycle->value.'-'.$contest->id),
            'source_fingerprint' => hash('sha256', 'source-'.$cycle->value.'-'.$contest->id),
            'snapshot_hash' => $batchHash,
            'sealed_by' => $actor->id,
            'sealed_at' => $sealedAt,
        ])->save();
        ApplicationReviewBatchItem::query()->create([
            'application_review_batch_id' => $batch->id,
            'administrative_process_id' => $process->id,
            'application_id' => $application->id,
            'application_review_id' => null,
            'process_number' => $process->process_number,
            'application_number' => $application->application_number,
            'application_public_id' => $application->public_id,
            'outcome' => $payload['outcome'],
            'technical_result' => $payload['technical_result'],
            'review_lock_version' => 1,
            'readiness_snapshot' => $payload['readiness'],
            'document_snapshot' => $payload['documents'],
            'snapshot_payload' => $payload,
            'source_fingerprint' => hash('sha256', 'item-'.$cycle->value.'-'.$application->id),
            'snapshot_hash' => $snapshotHash,
        ]);

        return $batch->refresh();
    }

    private function publication(
        ApplicationReviewBatch $batch,
        User $actor,
        string $publishedAt,
    ): ApplicationReviewPublication {
        return ApplicationReviewPublication::query()->create([
            'municipality_id' => $batch->municipality_id,
            'contest_id' => $batch->contest_id,
            'application_review_batch_id' => $batch->id,
            'cycle' => $batch->cycle,
            'sequence_number' => $batch->sequence_number,
            'status' => ApplicationReviewPublicationStatus::Published,
            'reason' => 'Publicação temporal de teste.',
            'item_count' => $batch->item_count,
            'publication_key' => hash('sha256', 'publication-key-'.$batch->id),
            'source_snapshot_hash' => $batch->snapshot_hash,
            'publication_hash' => hash('sha256', 'publication-hash-'.$batch->id),
            'published_by' => $actor->id,
            'published_at' => $publishedAt,
        ]);
    }

    private function publicationResult(
        ApplicationReviewPublication $publication,
        ApplicationReviewBatch $batch,
        Application $application,
        AdministrativeProcess $process,
        User $actor,
    ): ApplicationReviewPublicationResult {
        $item = $batch->items()->firstOrFail();
        $communication = CommunicationLog::factory()->create([
            'municipality_id' => $batch->municipality_id,
            'recipient_user_id' => $application->user_id,
            'created_by' => $actor->id,
        ]);
        $notification = OfficialNotification::factory()->create([
            'user_id' => $application->user_id,
            'application_id' => $application->id,
            'communication_log_id' => $communication->id,
            'created_by' => $actor->id,
        ]);
        $inApp = CommunicationDelivery::factory()->create([
            'communication_log_id' => $communication->id,
            'official_notification_id' => $notification->id,
            'channel' => CommunicationChannel::InApp,
        ]);
        $email = CommunicationDelivery::factory()->create([
            'communication_log_id' => $communication->id,
            'official_notification_id' => $notification->id,
            'channel' => CommunicationChannel::Email,
        ]);
        $resultPayload = [
            'schema_version' => 1,
            'cycle' => $batch->cycle->value,
            'process_number' => $process->process_number,
            'application_number' => $application->application_number,
            'outcome' => $item->outcome->value,
        ];
        $hasher = app(CanonicalJsonHasher::class);

        return ApplicationReviewPublicationResult::query()->create([
            'application_review_publication_id' => $publication->id,
            'application_review_batch_item_id' => $item->id,
            'municipality_id' => $batch->municipality_id,
            'contest_id' => $batch->contest_id,
            'administrative_process_id' => $process->id,
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'process_number' => $process->process_number,
            'application_number' => $application->application_number,
            'application_public_id' => $application->public_id,
            'outcome' => $item->outcome,
            'technical_result' => $item->technical_result,
            'result_payload' => $resultPayload,
            'source_snapshot_hash' => $item->snapshot_hash,
            'result_hash' => $hasher->hash($resultPayload),
            'notification_hash' => hash('sha256', 'notification-'.$item->id),
            'official_notification_id' => $notification->id,
            'communication_log_id' => $communication->id,
            'in_app_delivery_id' => $inApp->id,
            'email_delivery_id' => $email->id,
            'published_at' => $publication->published_at,
        ]);
    }
}
