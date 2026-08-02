<?php

namespace Tests\Feature;

use App\Contracts\Program53\Program53FaultInjector;
use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Enums\DocumentStatus;
use App\Enums\FeatureKey;
use App\Enums\Program53FailureCode;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReview;
use App\Models\Contest;
use App\Models\DocumentSubmission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewBatchService;
use App\Services\Administrative\ApplicationReviewReadinessService;
use App\Services\Program53\Resilience\ControlledProgram53FaultInjector;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use LogicException;
use Mockery\MockInterface;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53CReviewBatchSnapshotTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    /**
     * @var array<int, array{
     *     ready: bool,
     *     total_required: int,
     *     validated: int,
     *     submitted: int,
     *     under_review: int,
     *     missing: int,
     *     rejected: int,
     *     expired: int,
     *     blockers: list<string>
     * }>
     */
    private array $readiness = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->mock(
            ApplicationReviewReadinessService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('forProcess')
                    ->andReturnUsing(
                        fn (AdministrativeProcess $process): array => $this
                            ->readiness[$process->id]
                            ?? $this->readyReadiness(),
                    );
            },
        );
    }

    public function test_batch_routes_are_permission_first(): void
    {
        $expected = [
            'backoffice.application-review-batches.index' => [
                'permission:administrative_processes.view',
            ],
            'backoffice.application-review-batches.contest' => [
                'permission:administrative_processes.view',
                'permission:documents.view',
            ],
            'backoffice.application-review-batches.preview' => [
                'permission:administrative_processes.update',
            ],
            'backoffice.application-review-batches.seal' => [
                'permission:administrative_processes.update',
            ],
            'backoffice.application-review-batches.show' => [
                'permission:administrative_processes.view',
            ],
        ];

        foreach ($expected as $name => $permissions) {
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

            foreach ($permissions as $permission) {
                $this->assertContains($permission, $middleware);
            }

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
    }

    public function test_preview_is_read_only_and_seal_is_idempotent(): void
    {
        [$process, $review] = $this->readyProcess();
        $actor = $this->actorFor($process);
        $payload = $this->payload([$process->id]);
        $service = app(ApplicationReviewBatchService::class);

        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );

        $this->assertDatabaseCount('application_review_batches', 0);
        $this->assertSame(
            ApplicationReviewStatus::ReadyForClosure,
            $review->refresh()->status,
        );

        Notification::fake();
        $payload['preview_token'] = $preview['token'];
        $first = $service->seal(
            $this->contest($process),
            $actor,
            $payload,
        );
        $second = $service->seal(
            $this->contest($process),
            $actor,
            $payload,
        );

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('application_review_batches', 1);
        $this->assertDatabaseCount('application_review_batch_items', 1);
        $this->assertSame(
            ApplicationReviewStatus::Completed,
            $review->refresh()->status,
        );
        $this->assertSame(
            ApplicationReviewResult::Passed,
            $review->result,
        );
        Notification::assertNothingSent();
    }

    public function test_failure_after_items_rolls_back_seal_and_retry_is_safe(): void
    {
        [$process, $review] = $this->readyProcess();
        $actor = $this->actorFor($process);
        $payload = $this->payload([$process->id]);
        $preview = app(ApplicationReviewBatchService::class)->preview(
            $this->contest($process),
            $actor,
            $payload,
        );
        $payload['preview_token'] = $preview['token'];
        $this->app->instance(
            Program53FaultInjector::class,
            new ControlledProgram53FaultInjector([
                'after_batch_items_before_seal' => Program53FailureCode::DatabaseDeadlock,
            ]),
        );
        $service = app(ApplicationReviewBatchService::class);

        try {
            $service->seal($this->contest($process), $actor, $payload);
            $this->fail('A falha controlada deveria interromper a selagem.');
        } catch (\Throwable) {
            $this->assertDatabaseCount('application_review_batches', 0);
            $this->assertDatabaseCount('application_review_batch_items', 0);
            $this->assertSame(
                ApplicationReviewStatus::ReadyForClosure,
                $review->refresh()->status,
            );
        }

        $batch = $service->seal(
            $this->contest($process),
            $actor,
            $payload,
        );

        $this->assertDatabaseCount('application_review_batches', 1);
        $this->assertDatabaseCount('application_review_batch_items', 1);
        $this->assertSame(1, $batch->item_count);
    }

    public function test_document_change_invalidates_preview_token(): void
    {
        [$process] = $this->readyProcess();
        $actor = $this->actorFor($process);
        $payload = $this->payload([$process->id]);
        $service = app(ApplicationReviewBatchService::class);
        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );

        $document = DocumentSubmission::query()
            ->where('application_id', $process->application_id)
            ->firstOrFail();
        $document->forceFill(['notes' => 'Alteração concorrente.'])->save();
        $payload['preview_token'] = $preview['token'];

        $this->expectException(ValidationException::class);
        $service->seal(
            $this->contest($process),
            $actor,
            $payload,
        );
    }

    public function test_rejected_documents_create_correction_required_snapshot(): void
    {
        [$process, $review] = $this->readyProcess(
            reviewStatus: ApplicationReviewStatus::InProgress,
            documentStatus: DocumentStatus::Rejected,
        );
        $this->readiness[$process->id] = [
            'ready' => false,
            'total_required' => 1,
            'validated' => 0,
            'submitted' => 0,
            'under_review' => 0,
            'missing' => 0,
            'rejected' => 1,
            'expired' => 0,
            'blockers' => ['1 documento(s) rejeitado(s)'],
        ];
        $actor = $this->actorFor($process);
        $payload = $this->payload([$process->id]);
        $service = app(ApplicationReviewBatchService::class);
        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );

        $this->assertSame([], $preview['blockers']);
        $this->assertSame(
            ApplicationReviewBatchOutcome::CorrectionRequired,
            $preview['items'][0]->outcome,
        );

        $payload['preview_token'] = $preview['token'];
        $batch = $service->seal(
            $this->contest($process),
            $actor,
            $payload,
        );
        $item = $batch->items()->firstOrFail();

        $this->assertSame(
            ApplicationReviewBatchOutcome::CorrectionRequired,
            $item->outcome,
        );
        $this->assertSame(
            ApplicationReviewResult::RequiresCorrection,
            $review->refresh()->result,
        );
    }

    public function test_partial_contest_selection_is_rejected(): void
    {
        [$first] = $this->readyProcess();
        [$second] = $this->readyProcess($this->contest($first));
        $actor = $this->actorFor($first);
        $second->application->program->forceFill([
            'municipality_id' => $actor->municipality_id,
        ])->save();

        $this->expectException(ValidationException::class);
        app(ApplicationReviewBatchService::class)->preview(
            $this->contest($first),
            $actor,
            $this->payload([$first->id]),
        );
    }

    public function test_sealed_batch_and_items_reject_content_mutation(): void
    {
        [$process] = $this->readyProcess();
        $actor = $this->actorFor($process);
        $payload = $this->payload([$process->id]);
        $service = app(ApplicationReviewBatchService::class);
        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );
        $payload['preview_token'] = $preview['token'];
        $batch = $service->seal(
            $this->contest($process),
            $actor,
            $payload,
        );
        $item = $batch->items()->firstOrFail();

        try {
            $batch->forceFill(['reason' => 'Alteração proibida.'])->save();
            $this->fail('O lote deveria recusar alterações de conteúdo.');
        } catch (LogicException) {
            $this->assertDatabaseHas('application_review_batches', [
                'id' => $batch->id,
                'reason' => 'Fecho técnico da fase.',
            ]);
        }

        try {
            $item->forceFill(['outcome' => ApplicationReviewBatchOutcome::NotAssessed])->save();
            $this->fail('O item deveria ser imutável.');
        } catch (LogicException) {
            $this->assertDatabaseHas('application_review_batch_items', [
                'id' => $item->id,
                'outcome' => ApplicationReviewBatchOutcome::CompletePendingDecision->value,
            ]);
        }
    }

    /**
     * @return array{AdministrativeProcess, ApplicationReview}
     */
    private function readyProcess(
        ?Contest $contest = null,
        ApplicationReviewStatus $reviewStatus = ApplicationReviewStatus::ReadyForClosure,
        DocumentStatus $documentStatus = DocumentStatus::Validated,
    ): array {
        $process = AdministrativeProcess::factory()->create();
        $application = $process->application;
        $contest ??= $application->contest;
        $application->forceFill([
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
        ])->save();
        $process->forceFill([
            'program_id' => $contest->program_id,
            'contest_id' => $contest->id,
            'user_id' => $application->user_id,
            'status' => AdministrativeProcessStatus::DocumentReview,
        ])->save();
        $review = new ApplicationReview([
            'review_type' => ApplicationReviewType::Documental,
            'summary' => 'Análise documental de teste.',
        ]);
        $review->forceFill([
            'administrative_process_id' => $process->id,
            'application_id' => $application->id,
            'status' => $reviewStatus,
            'reviewed_by' => null,
            'started_at' => now()->subHour(),
            'ready_for_closure_at' => $reviewStatus === ApplicationReviewStatus::ReadyForClosure
                ? now()
                : null,
            'last_activity_at' => now(),
            'lock_version' => 1,
        ])->save();
        DocumentSubmission::factory()->create([
            'application_id' => $application->id,
            'adhesion_registration_id' => $application
                ->adhesion_registration_id,
            'user_id' => $application->user_id,
            'status' => $documentStatus->value,
            'reviewed_at' => now(),
            'validated_at' => $documentStatus === DocumentStatus::Validated
                ? now()
                : null,
            'rejected_at' => $documentStatus === DocumentStatus::Rejected
                ? now()
                : null,
        ]);

        return [
            $process->refresh()->load(['application.program', 'contest']),
            $review,
        ];
    }

    private function actorFor(AdministrativeProcess $process): User
    {
        $actor = $this->userWithPermissions([
            'administrative_processes.view',
            'administrative_processes.update',
            'documents.view',
        ]);
        $this->assignApplicationMunicipality(
            $actor,
            $process->application,
            FeatureKey::ApplicationReview,
        );
        $this->actingAs($actor);

        return $actor->refresh();
    }

    private function contest(AdministrativeProcess $process): Contest
    {
        $contest = $process->contest;
        $this->assertInstanceOf(Contest::class, $contest);

        return $contest;
    }

    /**
     * @param  list<int>  $processIds
     * @return array{
     *     cycle: ApplicationReviewBatchCycle,
     *     process_ids: list<int>,
     *     reason: string,
     *     preview_token: string|null
     * }
     */
    private function payload(array $processIds): array
    {
        return [
            'cycle' => ApplicationReviewBatchCycle::InitialReview,
            'process_ids' => $processIds,
            'reason' => 'Fecho técnico da fase.',
            'preview_token' => null,
        ];
    }

    /**
     * @return array{
     *     ready: bool,
     *     total_required: int,
     *     validated: int,
     *     submitted: int,
     *     under_review: int,
     *     missing: int,
     *     rejected: int,
     *     expired: int,
     *     blockers: list<string>
     * }
     */
    private function readyReadiness(): array
    {
        return [
            'ready' => true,
            'total_required' => 1,
            'validated' => 1,
            'submitted' => 0,
            'under_review' => 0,
            'missing' => 0,
            'rejected' => 0,
            'expired' => 0,
            'blockers' => [],
        ];
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::query()->create([
            'name' => 'review_batch_'.str()->random(8),
            'label' => 'Review batch test role',
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
