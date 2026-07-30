<?php

namespace Tests\Feature;

use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Enums\BulkApplicationReviewAction;
use App\Enums\DocumentStatus;
use App\Enums\FeatureKey;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReview;
use App\Models\Contest;
use App\Models\DocumentSubmission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrative\BulkApplicationReviewService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53BProgressiveBulkApplicationReviewTest extends TestCase
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

    public function test_workspace_routes_are_permission_first(): void
    {
        $expected = [
            'backoffice.application-review-workspace.index' => [
                'permission:administrative_processes.view',
            ],
            'backoffice.application-review-workspace.show' => [
                'permission:administrative_processes.view',
                'permission:documents.view',
            ],
            'backoffice.application-review-workspace.preview' => [
                'permission:administrative_processes.update',
            ],
            'backoffice.application-review-workspace.apply' => [
                'permission:administrative_processes.update',
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
            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with(
                        $item,
                        'role:',
                    ),
                ),
            );
        }
    }

    public function test_custom_analyst_can_open_municipal_workspace(): void
    {
        $process = $this->process();
        $analyst = $this->userWithPermissions([
            'administrative_processes.view',
            'documents.view',
        ]);
        $this->assignApplicationMunicipality(
            $analyst,
            $process->application,
            FeatureKey::ApplicationReview,
        );

        $this->actingAs($analyst)
            ->get(route(
                'backoffice.application-review-workspace.show',
                $this->contest($process),
            ))
            ->assertOk()
            ->assertSeeText($process->process_number);
    }

    public function test_candidate_cannot_open_workspace_even_with_permission(): void
    {
        $process = $this->process();
        $candidate = $this->userWithSystemRoleAndPermissions(
            'candidate',
            ['administrative_processes.view'],
        );
        $this->assignApplicationMunicipality(
            $candidate,
            $process->application,
            FeatureKey::ApplicationReview,
        );

        $this->actingAs($candidate)
            ->get(route(
                'backoffice.application-review-workspace.show',
                $this->contest($process),
            ))
            ->assertForbidden();
    }

    public function test_preview_does_not_mutate_assignment_and_apply_does(): void
    {
        $process = $this->process();
        $actor = $this->userWithPermissions([
            'administrative_processes.view',
            'administrative_processes.update',
            'administrative_processes.assign',
        ]);
        $this->assignApplicationMunicipality(
            $actor,
            $process->application,
            FeatureKey::ApplicationReview,
        );

        $assignee = $this->userWithPermissions([
            'administrative_processes.view',
            'documents.view',
        ]);
        $assignee->forceFill([
            'municipality_id' => $actor->municipality_id,
        ])->save();

        $payload = $this->payload(
            BulkApplicationReviewAction::AssignAnalyst,
            [$process->id],
            assignedTo: $assignee->id,
        );

        $service = app(BulkApplicationReviewService::class);
        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );

        $process->refresh();
        $this->assertNull($process->assigned_to);

        $payload['preview_token'] = $preview['token'];
        $service->apply(
            $this->contest($process),
            $actor,
            $payload,
        );

        $this->assertDatabaseHas('administrative_processes', [
            'id' => $process->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_stale_preview_token_is_rejected(): void
    {
        $process = $this->process();
        $actor = $this->userWithPermissions([
            'administrative_processes.view',
            'administrative_processes.update',
            'administrative_processes.assign',
        ]);
        $this->assignApplicationMunicipality(
            $actor,
            $process->application,
            FeatureKey::ApplicationReview,
        );

        $assignee = $this->userWithPermissions([
            'administrative_processes.view',
            'documents.view',
        ]);
        $assignee->forceFill([
            'municipality_id' => $actor->municipality_id,
        ])->save();

        $payload = $this->payload(
            BulkApplicationReviewAction::AssignAnalyst,
            [$process->id],
            assignedTo: $assignee->id,
        );

        $service = app(BulkApplicationReviewService::class);
        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );

        $process->forceFill([
            'internal_notes' => 'Alteração concorrente.',
        ])->save();

        $payload['preview_token'] = $preview['token'];

        $this->expectException(ValidationException::class);

        $service->apply(
            $this->contest($process),
            $actor,
            $payload,
        );
    }

    public function test_ready_preview_is_invalidated_when_document_state_changes(): void
    {
        $process = $this->process();
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

        $document = DocumentSubmission::factory()->create([
            'application_id' => $process->application_id,
            'adhesion_registration_id' => $process->application->adhesion_registration_id,
            'user_id' => $process->application->user_id,
            'status' => DocumentStatus::Validated->value,
        ]);

        $payload = $this->payload(
            BulkApplicationReviewAction::MarkReadyForClosure,
            [$process->id],
        );

        $service = app(BulkApplicationReviewService::class);
        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );

        $document->forceFill([
            'notes' => 'Alteração concorrente após a pré-visualização.',
        ])->save();

        $payload['preview_token'] = $preview['token'];

        $this->expectException(ValidationException::class);

        $service->apply(
            $this->contest($process),
            $actor,
            $payload,
        );
    }

    public function test_ready_for_closure_is_a_reversible_draft_state(): void
    {
        $process = $this->process();
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

        $service = app(BulkApplicationReviewService::class);

        $readyPayload = $this->payload(
            BulkApplicationReviewAction::MarkReadyForClosure,
            [$process->id],
        );
        $readyPreview = $service->preview(
            $this->contest($process),
            $actor,
            $readyPayload,
        );
        $readyPayload['preview_token'] = $readyPreview['token'];

        $service->apply(
            $this->contest($process),
            $actor,
            $readyPayload,
        );

        $review = ApplicationReview::query()
            ->where(
                'administrative_process_id',
                $process->id,
            )
            ->where(
                'review_type',
                ApplicationReviewType::Documental->value,
            )
            ->firstOrFail();

        $this->assertSame(
            ApplicationReviewStatus::ReadyForClosure,
            $review->status,
        );
        $this->assertNotNull($review->ready_for_closure_at);
        $this->assertNull($review->completed_at);
        $this->assertNull($review->result);

        $reopenPayload = $this->payload(
            BulkApplicationReviewAction::ReopenReview,
            [$process->id],
            reason: 'Novo elemento recebido antes do fecho.',
        );
        $reopenPreview = $service->preview(
            $this->contest($process),
            $actor,
            $reopenPayload,
        );
        $reopenPayload['preview_token'] = $reopenPreview['token'];

        $service->apply(
            $this->contest($process),
            $actor,
            $reopenPayload,
        );

        $review->refresh();

        $this->assertSame(
            ApplicationReviewStatus::InProgress,
            $review->status,
        );
        $this->assertNull($review->ready_for_closure_at);
    }

    public function test_document_bulk_validation_creates_no_candidate_notification(): void
    {
        $process = $this->process();
        $actor = $this->userWithPermissions([
            'administrative_processes.view',
            'administrative_processes.update',
            'documents.view',
            'documents.approve',
        ]);
        $this->assignApplicationMunicipality(
            $actor,
            $process->application,
            FeatureKey::ApplicationReview,
        );

        $document = DocumentSubmission::factory()->create([
            'application_id' => $process->application_id,
            'adhesion_registration_id' => $process->application->adhesion_registration_id,
            'user_id' => $process->application->user_id,
            'status' => DocumentStatus::UnderReview->value,
        ]);

        $before = $process->application
            ->officialNotifications()
            ->count();

        $payload = $this->payload(
            BulkApplicationReviewAction::ValidateDocuments,
            [$process->id],
            [$document->id],
        );

        $service = app(BulkApplicationReviewService::class);
        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );
        $payload['preview_token'] = $preview['token'];

        $service->apply(
            $this->contest($process),
            $actor,
            $payload,
        );

        $this->assertSame(
            DocumentStatus::Validated,
            $document->refresh()->status,
        );
        $this->assertSame(
            $before,
            $process->application
                ->officialNotifications()
                ->count(),
        );
    }

    public function test_invalid_document_transition_is_blocked_before_mutation(): void
    {
        $process = $this->process();
        $actor = $this->userWithPermissions([
            'administrative_processes.view',
            'administrative_processes.update',
            'documents.view',
            'documents.approve',
        ]);
        $this->assignApplicationMunicipality(
            $actor,
            $process->application,
            FeatureKey::ApplicationReview,
        );

        $document = DocumentSubmission::factory()->create([
            'application_id' => $process->application_id,
            'adhesion_registration_id' => $process->application->adhesion_registration_id,
            'user_id' => $process->application->user_id,
            'status' => DocumentStatus::Validated->value,
        ]);

        $payload = $this->payload(
            BulkApplicationReviewAction::MarkDocumentsUnderReview,
            [$process->id],
            [$document->id],
        );

        $service = app(BulkApplicationReviewService::class);
        $preview = $service->preview(
            $this->contest($process),
            $actor,
            $payload,
        );

        $this->assertNotSame([], $preview['blockers']);
        $payload['preview_token'] = $preview['token'];

        try {
            $service->apply(
                $this->contest($process),
                $actor,
                $payload,
            );
            $this->fail('A transição documental inválida deveria ter sido bloqueada.');
        } catch (ValidationException) {
            $this->assertSame(
                DocumentStatus::Validated,
                $document->refresh()->status,
            );
        }
    }

    private function process(): AdministrativeProcess
    {
        $process = AdministrativeProcess::factory()->create();
        $application = $process->application;

        $process->forceFill([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'user_id' => $application->user_id,
        ])->save();

        $process->refresh();

        return $process->load([
            'application',
            'contest',
            'candidate',
        ]);
    }

    private function contest(AdministrativeProcess $process): Contest
    {
        $contest = $process->contest;

        $this->assertInstanceOf(Contest::class, $contest);

        return $contest;
    }

    /**
     * @param  list<int>  $processIds
     * @param  list<int>  $documentIds
     * @return array{
     *     action: BulkApplicationReviewAction,
     *     process_ids: list<int>,
     *     document_ids: list<int>,
     *     assigned_to: int|null,
     *     reason: string|null,
     *     internal_notes: string|null,
     *     preview_token: string|null
     * }
     */
    private function payload(
        BulkApplicationReviewAction $action,
        array $processIds,
        array $documentIds = [],
        ?int $assignedTo = null,
        ?string $reason = null,
    ): array {
        return [
            'action' => $action,
            'process_ids' => $processIds,
            'document_ids' => $documentIds,
            'assigned_to' => $assignedTo,
            'reason' => $reason,
            'internal_notes' => 'Operação de teste em bloco.',
            'preview_token' => null,
        ];
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['status' => 'active']);

        $role = Role::query()->create([
            'name' => 'bulk_review_'.str()->random(8),
            'label' => 'Bulk review test role',
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

    /**
     * @param  list<string>  $permissions
     */
    private function userWithSystemRoleAndPermissions(
        string $roleName,
        array $permissions,
    ): User {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::query()
            ->where('name', $roleName)
            ->firstOrFail();

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);

        $role->permissions()->syncWithoutDetaching(
            $permissionIds,
        );
        $user->roles()->attach($role);

        return $user;
    }
}
