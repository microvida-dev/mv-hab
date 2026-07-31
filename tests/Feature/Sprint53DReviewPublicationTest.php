<?php

namespace Tests\Feature;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\FeatureKey;
use App\Jobs\DeliverProceduralEmail;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Support\CanonicalJsonHasher;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53DReviewPublicationTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_publication_routes_are_permission_first_and_candidate_routes_remain_owned(): void
    {
        $expected = [
            'backoffice.application-review-publications.index' => 'permission:administrative_processes.view',
            'backoffice.application-review-publications.create' => 'permission:administrative_processes.publish',
            'backoffice.application-review-publications.preview' => 'permission:administrative_processes.publish',
            'backoffice.application-review-publications.publish' => 'permission:administrative_processes.publish',
            'backoffice.application-review-publications.show' => 'permission:administrative_processes.view',
        ];

        foreach ($expected as $name => $permission) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
            );
            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );
            $this->assertContains($permission, $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
            $this->assertContains(
                'municipality.feature:applications.review',
                $middleware,
            );
            $this->assertFalse(collect($middleware)->contains(
                fn (string $item): bool => str_starts_with($item, 'role:'),
            ));
        }

        $this->assertNotNull(Route::getRoutes()->getByName(
            'candidate.application-review-results.index',
        ));
        $this->assertNotNull(Route::getRoutes()->getByName(
            'candidate.application-review-results.show',
        ));
    }

    public function test_publish_is_atomic_idempotent_and_queues_email_after_commit(): void
    {
        Queue::fake();
        [$batch, $actor] = $this->sealedBatch();
        $service = app(ApplicationReviewPublicationService::class);
        $preview = $service->preview(
            $batch,
            $actor,
            'Publicação oficial de teste.',
        );
        $payload = [
            'reason' => 'Publicação oficial de teste.',
            'preview_token' => $preview['token'],
        ];
        $first = $service->publish($batch, $actor, $payload);
        $second = $service->publish($batch, $actor, $payload);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('application_review_publications', 1);
        $this->assertDatabaseCount('application_review_publication_results', 1);
        $this->assertDatabaseCount('communication_logs', 1);
        $this->assertDatabaseCount('official_notifications', 1);
        $this->assertDatabaseCount('communication_deliveries', 2);
        $result = ApplicationReviewPublicationResult::query()->firstOrFail();
        $this->assertTrue($first->published_at->equalTo($result->published_at));
        $this->assertSame(
            CommunicationDeliveryStatus::Delivered,
            $result->inAppDelivery()->firstOrFail()->status,
        );
        $this->assertSame(
            CommunicationChannel::Email,
            $result->emailDelivery()->firstOrFail()->channel,
        );
        Queue::assertPushed(DeliverProceduralEmail::class, 1);
    }

    public function test_candidate_payload_does_not_expose_internal_snapshot_content(): void
    {
        Queue::fake();
        [$batch, $actor] = $this->sealedBatch();
        $service = app(ApplicationReviewPublicationService::class);
        $preview = $service->preview($batch, $actor, 'Publicar.');
        $service->publish($batch, $actor, [
            'reason' => 'Publicar.',
            'preview_token' => $preview['token'],
        ]);
        $result = ApplicationReviewPublicationResult::query()->firstOrFail();
        $encoded = json_encode($result->result_payload, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('internal_notes', $encoded);
        $this->assertStringNotContainsString('documents', $encoded);
        $this->assertStringNotContainsString('checksum', $encoded);
        $this->assertStringContainsString(
            'não constitui admissão',
            (string) $result->result_payload['message'],
        );
    }

    public function test_candidate_cannot_read_another_candidates_result(): void
    {
        Queue::fake();
        [$batch, $actor] = $this->sealedBatch();
        $service = app(ApplicationReviewPublicationService::class);
        $preview = $service->preview($batch, $actor, 'Publicar.');
        $service->publish($batch, $actor, [
            'reason' => 'Publicar.',
            'preview_token' => $preview['token'],
        ]);
        $result = ApplicationReviewPublicationResult::query()->firstOrFail();
        $other = User::factory()->create([
            'municipality_id' => $result->municipality_id,
        ]);
        $candidateRole = Role::query()->where('name', 'candidate')->firstOrFail();
        $other->roles()->syncWithoutDetaching([$candidateRole->id]);

        $this->assertFalse($other->can('view', $result));
        $this->assertTrue($result->user->can('view', $result));
    }

    public function test_changed_snapshot_invalidates_preview(): void
    {
        [$batch, $actor] = $this->sealedBatch();
        $service = app(ApplicationReviewPublicationService::class);
        $preview = $service->preview($batch, $actor, 'Publicar.');
        DB::table('application_review_batch_items')
            ->where('application_review_batch_id', $batch->id)
            ->update(['technical_result' => 'tampered']);

        $this->expectException(ValidationException::class);
        $service->publish($batch, $actor, [
            'reason' => 'Publicar.',
            'preview_token' => $preview['token'],
        ]);
    }

    /** @return array{ApplicationReviewBatch, User} */
    private function sealedBatch(): array
    {
        $process = AdministrativeProcess::factory()->create();
        $application = $process->application()->firstOrFail();
        $actor = $this->userWithPermissions([
            'administrative_processes.view',
            'administrative_processes.publish',
        ]);
        $this->assignApplicationMunicipality(
            $actor,
            $application,
            FeatureKey::ApplicationReview,
        );
        $application->user->forceFill([
            'municipality_id' => $actor->municipality_id,
            'email_verified_at' => now(),
        ])->save();
        $process->forceFill([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'user_id' => $application->user_id,
            'status' => AdministrativeProcessStatus::DocumentReview,
        ])->save();
        $payload = [
            'schema_version' => 1,
            'process' => [
                'id' => $process->id,
                'number' => $process->process_number,
                'status' => $process->status->value,
                'assigned_to' => null,
                'application_id' => $application->id,
                'contest_id' => $application->contest_id,
                'program_id' => $application->program_id,
            ],
            'application' => [
                'id' => $application->id,
                'public_id' => $application->public_id,
                'number' => $application->application_number,
                'status' => $application->status->value,
                'submitted_at' => null,
                'program_id' => $application->program_id,
                'contest_id' => $application->contest_id,
                'legal_regime' => null,
                'regulatory_snapshot_id' => null,
            ],
            'outcome' => ApplicationReviewBatchOutcome::CompletePendingDecision->value,
            'technical_result' => 'passed',
            'review' => ['internal_notes' => 'Nunca expor ao candidato.'],
            'readiness' => ['ready' => true, 'blockers' => []],
            'documents' => [['checksum' => 'private-checksum']],
        ];
        $hasher = app(CanonicalJsonHasher::class);
        $snapshotHash = $hasher->hash($payload);
        $batchHash = $hasher->hash([
            'schema_version' => 1,
            'contest_id' => $application->contest_id,
            'cycle' => ApplicationReviewBatchCycle::InitialReview->value,
            'items' => [[
                'application_id' => $application->id,
                'snapshot_hash' => $snapshotHash,
                'payload' => $payload,
            ]],
        ]);
        $batch = new ApplicationReviewBatch([
            'municipality_id' => $actor->municipality_id,
            'contest_id' => $application->contest_id,
            'cycle' => ApplicationReviewBatchCycle::InitialReview,
            'sequence_number' => 1,
            'status' => ApplicationReviewBatchStatus::Sealed,
            'reason' => 'Lote selado.',
            'item_count' => 1,
            'seal_key' => hash('sha256', 'seal-'.$process->id),
            'source_fingerprint' => hash('sha256', 'source-'.$process->id),
            'snapshot_hash' => $batchHash,
        ]);
        $batch->forceFill([
            'sealed_by' => $actor->id,
            'sealed_at' => now(),
        ])->save();
        ApplicationReviewBatchItem::query()->create([
            'application_review_batch_id' => $batch->id,
            'administrative_process_id' => $process->id,
            'application_id' => $application->id,
            'application_review_id' => null,
            'process_number' => $process->process_number,
            'application_number' => $application->application_number,
            'application_public_id' => $application->public_id,
            'outcome' => ApplicationReviewBatchOutcome::CompletePendingDecision,
            'technical_result' => 'passed',
            'review_lock_version' => 1,
            'readiness_snapshot' => ['ready' => true, 'blockers' => []],
            'document_snapshot' => [['checksum' => 'private-checksum']],
            'snapshot_payload' => $payload,
            'source_fingerprint' => hash('sha256', 'item-source-'.$process->id),
            'snapshot_hash' => $snapshotHash,
        ]);
        $candidateRole = Role::query()->where('name', 'candidate')->firstOrFail();
        $application->user->roles()->syncWithoutDetaching([$candidateRole->id]);
        $this->actingAs($actor);

        return [$batch->refresh()->load(['contest.program', 'items']), $actor];
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::query()->create([
            'name' => 'review_publication_'.str()->random(8),
            'label' => 'Review publication test role',
            'scope' => 'municipal',
            'is_system' => false,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');
        $this->assertCount(count($permissions), $permissionIds);
        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
