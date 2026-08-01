<?php

namespace Tests\Feature;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ContestDeadlineType;
use App\Enums\CorrectionRequestStatus;
use App\Enums\FeatureKey;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ContestDeadline;
use App\Models\CorrectionRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Support\CanonicalJsonHasher;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53ECorrectionProjectionTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_published_correction_result_projects_one_idempotent_request(): void
    {
        Queue::fake();
        [$batch, $actor, $application, $snapshotHash, $deadline] = $this
            ->sealedCorrectionBatch();
        $originalApplicationStatus = $application->status;
        $service = app(ApplicationReviewPublicationService::class);
        $preview = $service->preview(
            $batch,
            $actor,
            'Publicação inicial para aperfeiçoamento.',
        );
        $payload = [
            'reason' => 'Publicação inicial para aperfeiçoamento.',
            'preview_token' => $preview['token'],
        ];

        $first = $service->publish($batch, $actor, $payload);
        $second = $service->publish($batch, $actor, $payload);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('correction_requests', 1);
        $this->assertDatabaseCount('correction_request_items', 1);
        $request = CorrectionRequest::query()
            ->with(['items', 'publicationResult'])
            ->firstOrFail();
        $this->assertSame(CorrectionRequestStatus::Notified, $request->status);
        $this->assertSame($snapshotHash, $request->source_snapshot_hash);
        $this->assertTrue($request->response_deadline_at?->equalTo(
            $deadline->ends_at,
        ) ?? false);
        $this->assertTrue($request->candidate_visible);
        $this->assertNotNull($request->publicationResult);
        $this->assertSame(1, $request->items->count());
        $this->assertSame(
            $application->getMorphClass(),
            $request->items->firstOrFail()->target_type,
        );
        $this->assertSame(
            $originalApplicationStatus,
            $application->refresh()->status,
        );
    }

    /**
     * @return array{
     *     ApplicationReviewBatch,
     *     User,
     *     Application,
     *     string,
     *     ContestDeadline
     * }
     */
    private function sealedCorrectionBatch(): array
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
        $deadline = ContestDeadline::query()->create([
            'contest_id' => $application->contest_id,
            'type' => ContestDeadlineType::Corrections,
            'label' => ContestDeadlineType::Corrections->defaultLabel(),
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDays(5),
            'sort_order' => 30,
        ]);
        $payload = [
            'schema_version' => 2,
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
            'outcome' => ApplicationReviewBatchOutcome::CorrectionRequired->value,
            'technical_result' => 'requires_correction',
            'review' => null,
            'readiness' => [
                'ready' => false,
                'missing' => 1,
                'blockers' => ['1 documento obrigatório em falta'],
            ],
            'documents' => [],
            'findings' => [[
                'finding_status' => 'missing',
                'document_status' => 'missing',
                'target_type' => $application->getMorphClass(),
                'target_id' => $application->id,
                'target_label' => $application->application_number,
                'document_type_id' => null,
                'required_document_id' => null,
                'requirement_instance' => 1,
                'title' => 'Documento obrigatório',
                'description' => 'Submeta o documento solicitado.',
                'is_required' => true,
                'sort_order' => 1,
            ]],
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
            'reason' => 'Lote inicial selado.',
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
            'outcome' => ApplicationReviewBatchOutcome::CorrectionRequired,
            'technical_result' => 'requires_correction',
            'review_lock_version' => 1,
            'readiness_snapshot' => $payload['readiness'],
            'document_snapshot' => [],
            'snapshot_payload' => $payload,
            'source_fingerprint' => hash('sha256', 'item-source-'.$process->id),
            'snapshot_hash' => $snapshotHash,
        ]);
        $candidateRole = Role::query()->where('name', 'candidate')->firstOrFail();
        $application->user->roles()->syncWithoutDetaching([$candidateRole->id]);
        $this->actingAs($actor);

        return [
            $batch->refresh()->load(['contest.program', 'items']),
            $actor,
            $application,
            $snapshotHash,
            $deadline->refresh(),
        ];
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::query()->create([
            'name' => 'correction_projection_'.str()->random(8),
            'label' => 'Correction projection test role',
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
