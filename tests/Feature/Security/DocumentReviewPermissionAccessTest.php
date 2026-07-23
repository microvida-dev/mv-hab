<?php

namespace Tests\Feature\Security;

use App\Enums\DocumentStatus;
use App\Enums\FeatureKey;
use App\Models\DocumentAiAnalysis;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\Municipality;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\DocumentIntelligence\DocumentAiManualAnalysisService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class DocumentReviewPermissionAccessTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    private Municipality $municipality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationIntake,
            FeatureKey::ApplicationReview,
        );
        Storage::fake('local');
    }

    public function test_document_review_routes_use_expected_permissions(): void
    {
        $expected = [
            'admin.document-reviews.index' => 'permission:documents.view',

            'admin.document-reviews.show' => 'permission:documents.view',

            'admin.document-reviews.preview' => 'permission:documents.view',

            'admin.document-reviews.download' => 'permission:documents.view',

            'admin.document-reviews.under-review' => 'permission:documents.approve',

            'admin.document-reviews.validate' => 'permission:documents.approve',

            'admin.document-reviews.reject' => 'permission:documents.reject',

            'admin.document-reviews.document-ai' => 'permission:documents.approve',
        ];

        foreach ($expected as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                "Route [{$routeName}] is not registered.",
            );

            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:')
                ),
            );

            $this->assertContains($permissionMiddleware, $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
            $this->assertContains('municipality.feature:applications.review', $middleware);
        }
    }

    public function test_custom_role_with_documents_view_can_access_review_index(): void
    {
        $user = $this->userWithCustomRole(['documents.view']);

        $this->actingAs($user)
            ->get(route('admin.document-reviews.index'))
            ->assertOk();
    }

    public function test_user_without_documents_view_cannot_access_review_index(): void
    {
        $user = $this->userWithCustomRole(['documents.approve']);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-reviews.index'))
            ->assertForbidden();
    }

    public function test_custom_role_with_documents_view_can_access_document_show(): void
    {
        $user = $this->userWithCustomRole(['documents.view']);
        $submission = $this->submission();

        $this->actingAs($user)
            ->get(route('admin.document-reviews.show', $submission))
            ->assertOk();

        $this->assertDatabaseHas('document_access_logs', [
            'document_submission_id' => $submission->id,
            'user_id' => $user->id,
            'action' => 'view',
        ]);
    }

    public function test_custom_role_with_documents_view_can_preview_document(): void
    {
        $user = $this->userWithCustomRole(['documents.view']);
        $submission = $this->submissionWithStoredVersion();

        $this->actingAs($user)
            ->get(route('admin.document-reviews.preview', $submission))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        $this->assertDatabaseHas('document_access_logs', [
            'document_submission_id' => $submission->id,
            'user_id' => $user->id,
            'action' => 'preview',
        ]);
    }

    public function test_custom_role_with_documents_view_can_download_document(): void
    {
        $user = $this->userWithCustomRole(['documents.view']);
        $submission = $this->submissionWithStoredVersion();

        $this->actingAs($user)
            ->get(route('admin.document-reviews.download', $submission))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertDatabaseHas('document_access_logs', [
            'document_submission_id' => $submission->id,
            'user_id' => $user->id,
            'action' => 'download',
        ]);
    }

    public function test_documents_view_alone_cannot_mark_document_under_review(): void
    {
        $user = $this->userWithCustomRole(['documents.view']);
        $submission = $this->submission();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('admin.document-reviews.under-review', $submission),
                ['internal_notes' => 'Sem autorização operacional.'],
            )
            ->assertForbidden();
    }

    public function test_custom_role_with_documents_approve_can_mark_document_under_review(): void
    {
        $user = $this->userWithCustomRole(['documents.approve']);
        $submission = $this->submission();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('admin.document-reviews.under-review', $submission),
                ['internal_notes' => 'Análise iniciada.'],
            )
            ->assertRedirect(
                route('admin.document-reviews.show', $submission),
            );

        $this->assertDatabaseHas('document_submissions', [
            'id' => $submission->id,
            'status' => DocumentStatus::UnderReview->value,
        ]);
    }

    public function test_custom_role_with_documents_approve_can_validate_document(): void
    {
        $user = $this->userWithCustomRole(['documents.approve']);

        $submission = $this->submission([
            'status' => DocumentStatus::UnderReview->value,
        ]);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('admin.document-reviews.validate', $submission),
                ['internal_notes' => 'Documento conforme.'],
            )
            ->assertRedirect(
                route('admin.document-reviews.show', $submission),
            );

        $this->assertDatabaseHas('document_submissions', [
            'id' => $submission->id,
            'status' => DocumentStatus::Validated->value,
        ]);
    }

    public function test_custom_role_with_documents_reject_can_reject_document(): void
    {
        $user = $this->userWithCustomRole(['documents.reject']);
        $submission = $this->submission();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('admin.document-reviews.reject', $submission),
                [
                    'rejection_reason' => 'Documento ilegível e incompleto.',
                    'internal_notes' => 'Solicitar nova submissão.',
                ],
            )
            ->assertRedirect(
                route('admin.document-reviews.show', $submission),
            );

        $this->assertDatabaseHas('document_submissions', [
            'id' => $submission->id,
            'status' => DocumentStatus::Rejected->value,
        ]);
    }

    public function test_custom_role_with_documents_approve_reaches_manual_ai_analysis(): void
    {
        $user = $this->userWithCustomRole(['documents.approve']);
        $submission = $this->submission();

        $analysis = DocumentAiAnalysis::factory()->create([
            'document_submission_id' => $submission->id,
        ]);

        $this->mock(
            DocumentAiManualAnalysisService::class,
            function (MockInterface $mock) use (
                $submission,
                $user,
                $analysis,
            ): void {
                $mock->shouldReceive('execute')
                    ->once()
                    ->withArgs(
                        fn (
                            DocumentSubmission $receivedSubmission,
                            User $receivedUser,
                        ): bool => $receivedSubmission->is($submission)
                            && $receivedUser->is($user)
                    )
                    ->andReturn($analysis);
            },
        );

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('admin.document-reviews.document-ai', $submission),
            )
            ->assertRedirect(
                route(
                    'backoffice.document-ai.assistant.show',
                    $analysis,
                ),
            );
    }

    public function test_candidate_cannot_access_document_review_backoffice_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'documents.view',
                'documents.approve',
                'documents.reject',
            ],
        );

        $submission = $this->submission();

        $this->actingAs($user)
            ->get(route('admin.document-reviews.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.document-reviews.show', $submission))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(
                route('admin.document-reviews.validate', $submission),
            )
            ->assertForbidden();
    }

    public function test_verified_auditor_can_view_but_cannot_decide_or_run_ai(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'auditor',
            permissions: [
                'documents.view',
                'documents.approve',
                'documents.reject',
            ],
        );

        $submission = $this->submission();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-reviews.show', $submission))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('admin.document-reviews.validate', $submission),
            )
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('admin.document-reviews.reject', $submission),
                ['rejection_reason' => 'Tentativa indevida.'],
            )
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->post(
                route('admin.document-reviews.document-ai', $submission),
            )
            ->assertForbidden();
    }

    /** @param array<string, mixed> $attributes */
    private function submission(array $attributes = []): DocumentSubmission
    {
        $candidate = User::factory()->create(['municipality_id' => $this->municipality->id]);

        return DocumentSubmission::factory()->create($attributes + ['user_id' => $candidate->id]);
    }

    private function submissionWithStoredVersion(): DocumentSubmission
    {
        $submission = $this->submission();

        $path = 'documents/tests/document-'.$submission->id.'.pdf';

        Storage::disk('local')->put(
            $path,
            '%PDF-1.4 test document',
        );

        $version = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'original_filename' => 'documento-teste.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $submission->forceFill([
            'current_version_id' => $version->id,
        ])->save();

        return $submission->refresh();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'document_review_'.str()->random(8),
            'label' => 'Document review test role',
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
        $user = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);

        $role = Role::query()
            ->where('name', $roleName)
            ->firstOrFail();

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
